<?php
/*
 * Fusion - PHP Package Manager
 * Copyright © Valvoid
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Valvoid\Fusion\Tests\Tasks\Copy;

use Exception;
use Valvoid\Fusion\Tasks\Copy\Copy;
use Valvoid\Fusion\Tests\Tasks\Copy\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Copy\Mocks\DirectoryMock;
use Valvoid\Fusion\Tests\Tasks\Copy\Mocks\DirMock;
use Valvoid\Fusion\Tests\Tasks\Copy\Mocks\ExternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Copy\Mocks\FileMock;
use Valvoid\Fusion\Tests\Tasks\Copy\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Copy\Mocks\InternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Copy\Mocks\InterpreterMock;
use Valvoid\Fusion\Tests\Tasks\Copy\Mocks\LogMock;
use Valvoid\Fusion\Tests\Tasks\Copy\Mocks\ParserMock;
use Valvoid\Fusion\Tests\Test;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;

class CopyTest extends Test
{
    protected string|array $coverage = Copy::class;
    private BoxMock $box;

    public function __construct()
    {
        $this->box = new BoxMock;

        $this->testPackageCategory();
        $this->testCustomMigration();

        $this->box::unsetInstance();
    }

    public function testPackageCategory(): void
    {
        try {
            $directory = new DirectoryMock;
            $dir = new DirMock;
            $file = new FileMock;
            $group = new GroupMock;
            $task = new Copy(
                box: $this->box,
                group: $group,
                log: new LogMock,
                directory: $directory,
                file: $file,
                dir: $dir,
                config: []);

            $directory->cache = function () {return "/tmp";};
            $group->externalMetas = [];
            $group->internalMetas["i0"] = new InternalMetadataMock(
                InternalCategory::RECYCLABLE, [
                "source" => "/s0",
                "structure" => [
                    "stateful" => "/state",
                    "extensions" => [],
                    "sources" => [
                        "/deps" => ""
                    ]
                ]
            ]);

            $group->internalMetas["i1"] = new InternalMetadataMock(
                InternalCategory::MOVABLE, [
                "source" => "/s0/deps/i1",
                "structure" => [
                    "stateful" => "/state",
                    "sources" => [],
                    "extensions" => []
                ]
            ]);

            $group->internalMetas["i2"] = new InternalMetadataMock(
                InternalCategory::OBSOLETE, [
                   // "source" => "/s0/deps/i2",
                ]);

            $create =
            $filenames =
            $copy =
            $is = [];

            $directory->create = function (string $dir) use (&$create) {
                $create[] = $dir;
            };

            $dir->filenames = function (string $dir) use (&$filenames) {
                $filenames[] = $dir;

                if ($dir == "/s0")
                    return ["d0", "f0"];

                // test nested
                if ($dir == "/s0/d0")
                    return ["f1"];

                if ($dir == "/s0/deps/i1")
                    return ["f2"];

                return [];
            };

            $file->is = function (string $file) use (&$is) {
                $is[] = $file;

                return $file == "/s0/f0" ||
                    $file == "/s0/d0/f1" ||
                    $file == "/s0/deps/i1/f2";
            };

            $directory->copy = function (string $from, string $to) use (&$copy) {
                $copy[] = "$from->$to";
            };

            $task->execute();

            if ($create != [
                    "/tmp/i0",
                    "/tmp/i0/d0",
                    "/tmp/i1"] ||
                $filenames != [
                    "/s0",
                    "/s0/d0",
                    "/s0/deps/i1"] ||
                $is != [
                    "/s0/d0",
                    "/s0/d0/f1",
                    "/s0/f0",
                    "/s0/deps/i1/f2"] ||
                $copy != [
                    "/s0/d0/f1->/tmp/i0/d0/f1",
                    "/s0/f0->/tmp/i0/f0",
                    "/s0/deps/i1/f2->/tmp/i1/f2"])
                $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }

    public function testCustomMigration(): void
    {
        try {
            $directory = new DirectoryMock;
            $dir = new DirMock;
            $file = new FileMock;
            $group = new GroupMock;
            $task = new Copy(
                box: $this->box,
                group: $group,
                log: new LogMock,
                directory: $directory,
                file: $file,
                dir: $dir,
                config: []);

            $directory->cache = function () {return "/tmp";};
            $group->internalMetas["i0"] = new InternalMetadataMock(
                InternalCategory::OBSOLETE, [
                "version" => "0"
            ]);

            $group->externalMetas["i0"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE, [
                "version" => "1"
            ]);

            $migrate =
            $compare =
            $semver = [];

            ParserMock::$version = function (string $version) use (&$semver) {
                $semver[] = $version;

                return [$version];
            };

            InterpreterMock::$compare = function (array $a, array $b) use (&$compare) {
                $compare[] = [$a, $b];

                return true;
            };

            $group->internalMetas["i0"]->migrate = function () use (&$migrate) {
                $migrate[] = "0";

                return true;
            };

            $group->externalMetas["i0"]->migrate = function () use (&$migrate) {
                $migrate[] = "1";

                return true;
            };

            $task->execute();

            InterpreterMock::$compare =
            ParserMock::$version = null;

            if ($semver != ["0", "1"] ||
                $migrate != ["1"] ||
                $compare !== [[["1"], ["0"]]])
                $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }
}