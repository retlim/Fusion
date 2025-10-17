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

namespace Valvoid\Fusion\Tests\Tasks\Stack;

use Exception;
use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;
use Valvoid\Fusion\Tasks\Stack\Stack;
use Valvoid\Fusion\Tests\Tasks\Stack\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Stack\Mocks\DirectoryMock;
use Valvoid\Fusion\Tests\Tasks\Stack\Mocks\ExternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Stack\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Stack\Mocks\InternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Stack\Mocks\LogMock;
use Valvoid\Fusion\Tests\Test;

class StackTest extends Test
{
    protected string|array $coverage = [
        Stack::class,

        // ballast
        Box::class
    ];

    private BoxMock $box;
    private LogMock $log;

    public function __construct()
    {
        $this->box = new BoxMock;
        $this->log = new LogMock;

        $this->test();

        $this->box::unsetInstance();
    }

    public function test(): void
    {
        try {
            $directory = new DirectoryMock;
            $group = new GroupMock;
            $stack = new Stack(
                box: $this->box,
                group: $group,
                log: $this->log,
                directory: $directory,
                config: []
            );

            $rename =
            $onCopy =
            $onDownload =
            $create = [];
            $directory->state = function () {return "/tmp/state";};
            $directory->packages = function () {return "/tmp/packages";};
            $group->hasDownloadable = true;
            $group->implication = [
                "i3" => ["implication" => []],
                "i4" => ["implication" => [
                    "i2" => ["implication" => []]
                ]]
            ];

            $group->internalMetas["i0"] = new InternalMetadataMock(
                InternalCategory::RECYCLABLE, [
                "id" => "i0",
                "dir" => "",
            ]);

            $group->internalMetas["i0"]->copy = function () use (&$onCopy) {
                $onCopy[] = "i0";
                return true;
            };
            $group->internalRoot = $group->internalMetas["i0"];
            $group->internalMetas["i1"] = new InternalMetadataMock(
                InternalCategory::OBSOLETE, [
                "dir" => "/deps/d1",
            ]);

            $group->internalMetas["i2"] = new InternalMetadataMock(
                InternalCategory::MOVABLE, [
                "dir" => "/deps/d2",
            ]);

            $group->internalMetas["i3"] = new InternalMetadataMock(
                InternalCategory::RECYCLABLE, [
                "dir" => "/deps/d3",
            ]);

            $group->externalMetas["i2"] = new ExternalMetadataMock(
                ExternalCategory::REDUNDANT, [
                "dir" => "/deps/d2",
            ]);
            $group->externalMetas["i2"]->copy = function () use (&$onCopy) {
                $onCopy[] = "i2";
                return true;
            };
            $group->externalMetas["i3"] = new ExternalMetadataMock(
                ExternalCategory::REDUNDANT, [
                "dir" => "/deps/d3",
            ]);
            $group->externalMetas["i3"]->copy = function () use (&$onCopy) {
                $onCopy[] = "i3";
                return true;
            };
            $group->externalMetas["i4"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE, [
                "dir" => "/deps/d4",
            ]);
            $group->externalMetas["i4"]->download = function () use (&$onDownload) {
                $onDownload[] = "i4";
                return true;
            };
            $directory->create = function (string $file) use (&$create) {
                $create[] = $file;
            };

            $directory->rename = function (string $from, string $to) use (&$rename) {
                $rename[] = "$from->$to";
            };

            $stack->execute();

            if ($create != [
                    "/tmp/state",
                    "/tmp/state/deps/d2",
                    "/tmp/state/deps/d3",
                    "/tmp/state/deps/d4",
                ] ||
                $onDownload != ["i4"] ||
                $onCopy != ["i3", "i2", "i0"] ||
                $rename != [
                    "/tmp/packages/i0->/tmp/state",
                    "/tmp/packages/i2->/tmp/state/deps/d2",
                    "/tmp/packages/i3->/tmp/state/deps/d3",
                    "/tmp/packages/i4->/tmp/state/deps/d4"
                ])
                $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }
}