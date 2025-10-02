<?php
/**
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
 */

namespace Valvoid\Fusion\Tests\Tasks\Image;

use Exception;
use Valvoid\Fusion\Tasks\Image\Image;
use Valvoid\Fusion\Tests\Tasks\Image\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Image\Mocks\BuilderMock;
use Valvoid\Fusion\Tests\Tasks\Image\Mocks\ConfigMock;
use Valvoid\Fusion\Tests\Tasks\Image\Mocks\DirMock;
use Valvoid\Fusion\Tests\Tasks\Image\Mocks\FileMock;
use Valvoid\Fusion\Tests\Tasks\Image\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Image\Mocks\LogMock;
use Valvoid\Fusion\Tests\Tasks\Image\Mocks\MetadataMock;
use Valvoid\Fusion\Tests\Test;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class ImageTest extends Test
{
    protected string|array $coverage = Image::class;
    private BoxMock $box;
    private LogMock $log;
    private GroupMock $group;
    private ConfigMock $config;
    private DirMock $dir;
    private FileMock $file;

    public function __construct()
    {
        $this->box = new BoxMock;
        $this->log = new LogMock;
        $this->group = new GroupMock;
        $this->config = new ConfigMock;
        $this->dir = new DirMock;
        $this->file = new FileMock;
        $this->box->group = $this->group;
        $this->box->config = $this->config;

        $this->testMetas();

        $this->box::unsetInstance();
    }

    public function testMetas(): void
    {
        try {
            $task = new Image(
                $this->box,
                $this->log,
                $this->file,
                $this->dir,

                // task group id
                ["group" => true]
            );

            /** @var  $builder array<BuilderMock> */
            $builder =
            $get =
            $crumb =
            $require =
            $filenames =
            $is =
            $exist = [];

            $this->config->get = function (array $breadcrumb) use (&$crumb) {
                $crumb[] = $breadcrumb;

                return "/d0";
            };

            $this->file->exists = function (string $file) use (&$exist) {
                $exist[] = $file;

                return $file == "/d0/fusion.json" ||
                    $file == "/d0/deps/i1/fusion.json" ||
                    $file == "/d0/deps/i1/fusion.bot.php" ||
                    $file == "/d0/deps/i2/fusion.json" ||
                    $file == "/d0/fusion.local.php" ||
                    $file == "/d0/fusion.dev.php" ||
                    $file == "/d0/fusion.bot.php";
            };

            $this->file->get = function (string $file) use (&$get) {
                $get[] = $file;

                if ($file == "/d0/fusion.json")
                    return "c0p"; // production content

                if ($file == "/d0/deps/i1/fusion.json")
                    return "c1p";

                if ($file == "/d0/deps/i2/fusion.json")
                    return "c2p";

                return false;
            };

            $this->file->require = function (string $file) use (&$require) {
                $require[] = $file;

                if ($file == "/d0/fusion.local.php")
                    return ["c0l"];

                if ($file == "/d0/fusion.dev.php")
                    return ["c0d"];

                if ($file == "/d0/fusion.bot.php")
                    return ["c0b"];

                if ($file == "/d0/deps/i1/fusion.bot.php")
                    return ["c1b"];

                return false;
            };

            $this->dir->is = function (string $dir) use (&$is) {
                $is[] = $dir;

                return $dir == "/d0/deps" ||
                    $dir == "/d0/deps/i1" ||
                    $dir == "/d0/deps/i2";
            };

            $this->dir->filenames = function (string $dir) use (&$filenames) {
                $filenames[] = $dir;

                if ($dir == "/d0/deps")
                    return ["i1", "i2"];

                return [];
            };

            $this->box->builder = function (array $args) use (&$builder) {
                $mock = new BuilderMock(...$args);

                // root
                if ($args["dir"] == "") {
                    $builder["i0"] = $mock;
                    $mock->metadata = new MetadataMock([
                        "id" => "i0",
                        "dir" => $args["dir"],
                        "structure" => [
                            "sources" => [
                                "/deps" => [
                                    "/i1" => "#",
                                    "/i2" => "#"
                                ]
                            ]
                        ]
                    ]);

                } else {
                    $id = substr($args["source"], -2);
                    $builder[$id] = $mock;
                    $mock->metadata = new MetadataMock([
                        "id" => $id,
                        "dir" => $args["dir"],
                        "structure" => [
                            "sources" => []
                        ]
                    ]);
                }

                return $mock;
            };

            $task->execute();

            if ($crumb !== [["dir", "path"]])
                $this->handleFailedTest();

            if ($filenames !== ["/d0/deps"])
                $this->handleFailedTest();

            if ($is !== ["/d0/deps",
                    "/d0/deps/i1",
                    "/d0/deps/i2"])
                $this->handleFailedTest();

            if ($require !== ["/d0/fusion.local.php",
                    "/d0/fusion.dev.php",
                    "/d0/fusion.bot.php",
                    "/d0/deps/i1/fusion.bot.php"])
                $this->handleFailedTest();

            if ($exist !== ["/d0/fusion.json",
                    "/d0/fusion.local.php",
                    "/d0/fusion.dev.php",
                    "/d0/fusion.bot.php",
                    "/d0/deps/fusion.json",
                    "/d0/deps/i1/fusion.json",
                    "/d0/deps/i1/fusion.bot.php",
                    "/d0/deps/i2/fusion.json",
                    "/d0/deps/i2/fusion.bot.php"])
                $this->handleFailedTest();

            if ($get !== ["/d0/fusion.json",
                    "/d0/deps/i1/fusion.json",
                    "/d0/deps/i2/fusion.json"])
                $this->handleFailedTest();

            if (array_keys($builder) !== ["i0", "i1", "i2"] ||
                array_keys($this->group->metas) !== ["i0", "i1", "i2"]) {
                $this->handleFailedTest();

                return;
            }

            $b0 = $builder["i0"];
            $b1 = $builder["i1"];
            $b2 = $builder["i2"];

            if ($b0->dir !== "" ||
                $b0->source !== "/d0" ||
                $b1->dir !== "/deps" ||
                $b1->source !== "/d0/deps/i1" ||
                $b2->dir !== "/deps" ||
                $b2->source !== "/d0/deps/i2")
                $this->handleFailedTest();

            if (($this->group->metas["i0"] ?? 0) !== $b0->metadata ||
                ($this->group->metas["i1"] ?? 0) !== $b1->metadata ||
                ($this->group->metas["i2"] ?? 0) !== $b2->metadata)
                $this->handleFailedTest();

            $empty = ["file" => "", "content" => ""];

            if ($b0->production["file"] !== "/d0/fusion.json" ||
                $b0->production["content"] !== "c0p" ||
                $b0->development["file"] !== "/d0/fusion.dev.php" ||
                $b0->development["content"] !== ["c0d"] ||
                $b0->local["file"] !== "/d0/fusion.local.php" ||
                $b0->local["content"] !== ["c0l"] ||
                $b0->bot["file"] !== "/d0/fusion.bot.php" ||
                $b0->bot["content"] !== ["c0b"] ||

                $b1->production["file"] !== "/d0/deps/i1/fusion.json" ||
                $b1->production["content"] !== "c1p" ||
                $b1->development !== $empty ||
                $b1->local !== $empty ||
                $b1->bot["file"] !== "/d0/deps/i1/fusion.bot.php" ||
                $b1->bot["content"] !== ["c1b"] ||

                $b2->production["file"] !== "/d0/deps/i2/fusion.json" ||
                $b2->production["content"] !== "c2p" ||
                $b2->development !== $empty ||
                $b2->local !== $empty ||
                $b2->bot !== $empty)
                $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }
}