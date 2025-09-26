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

namespace Valvoid\Fusion\Tests\Tasks\Shift;

use Exception;
use Valvoid\Fusion\Bus\Events\Root;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Tasks\Shift\Shift;
use Valvoid\Fusion\Tests\Tasks\Shift\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Shift\Mocks\BusMock;
use Valvoid\Fusion\Tests\Tasks\Shift\Mocks\DirectoryMock;
use Valvoid\Fusion\Tests\Tasks\Shift\Mocks\DirMock;
use Valvoid\Fusion\Tests\Tasks\Shift\Mocks\ExternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Shift\Mocks\FileMock;
use Valvoid\Fusion\Tests\Tasks\Shift\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Shift\Mocks\InternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Shift\Mocks\LogMock;
use Valvoid\Fusion\Tests\Test;

/**
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ShiftTest extends Test
{
    protected string|array $coverage = Shift::class;
    private BoxMock $box;
    private LogMock $log;

    public function __construct()
    {
        $this->box = new BoxMock;
        $this->log = new LogMock;

        // new root version
        $this->testShiftRecursive();

        // new root with new cache dir
        $this->testShiftRecursiveCache();

        // new root with new cache dir intersection
        $this->testShiftRecursiveCacheIntersection();
        $this->testShiftNested();

        // check if persisted inside "other" dir
        $this->testShiftRecursiveWithExecutedFiles();
        $this->testShiftNestedWithExecutedFiles();
        $this->testRefresh();

        $this->box::unsetInstance();
    }

    public function testRefresh(): void
    {
        try {
            $bus = new BusMock;
            $group = new GroupMock;
            $directory = new DirectoryMock;
            $file = new FileMock;
            $dir = new DirMock;
            $task = new Shift(
                box: $this->box,
                bus: $bus,
                group: $group,
                log: $this->log,
                directory: $directory,
                file: $file,
                dir: $dir,
                config: []
            );

            $delete =
            $clear =
            $onDelete =
            $onUpdate = [];
            $group->hasDownloadable = false;
            $group->internalMetas["i0"] = new InternalMetadataMock(
                InternalCategory::RECYCLABLE, []);

            $group->internalMetas["i1"] = new InternalMetadataMock(
                InternalCategory::OBSOLETE, [
                    "source" => "/si1",
                    "dir" => "/di1"
            ]);

            $group->internalMetas["i0"]->update = function () use (&$onUpdate) {
                $onUpdate[] = "i0";
            };

            $group->internalMetas["i1"]->delete = function () use (&$onDelete) {
                $onDelete[] = "i1";
            };

            $group->internalRoot = $group->internalMetas["i0"];
            $directory->root = function () {return "/root";};
            $directory->cache = function () {return "/root/c";};
            $directory->task = function () {return "/tmp/task";};
            $directory->state = function () {return "/tmp/state";};
            $directory->other = function () {return "/tmp/other";};
            $directory->packages = function () {return "/tmp/packages";};
            $directory->delete = function (string $file) use (&$delete) {
                $delete[] = $file;
            };

            $directory->clear = function (string $dir, string $path) use (&$clear) {
                $clear[] = "$dir->$path";
            };

            $task->execute();

            if ($onDelete != ["i1"] ||
                $onUpdate != ["i0"] ||
                $delete != ["/si1"] ||
                $clear != ["/root->/di1"])
                $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }

    public function testShiftRecursive(): void
    {
        try {
            $bus = new BusMock;
            $group = new GroupMock;
            $directory = new DirectoryMock;
            $file = new FileMock;
            $dir = new DirMock;
            $task = new Shift(
                box: $this->box,
                bus: $bus,
                group: $group,
                log: $this->log,
                directory: $directory,
                file: $file,
                dir: $dir,
                config: []
            );

            $filenames =
            $delete =
            $rename =
            $exists =
            $onDelete =
            $onInstall =
            $is = [];
            $group->hasDownloadable = true;
            $group->internalMetas["i0"] = new InternalMetadataMock(
                InternalCategory::OBSOLETE, [
                "structure" => [
                    "cache" => "/c"
                ]
            ]);

            $group->internalMetas["i0"]->delete = function () use (&$onDelete) {
                $onDelete[] = "i0";
            };

            $group->internalRoot = $group->internalMetas["i0"];
            $group->externalMetas["i0"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE, [
                "structure" => [
                    "cache" => "/c"
                ]
            ]);

            $group->externalRoot = $group->externalMetas["i0"];
            $group->externalMetas["i0"]->install = function () use (&$onInstall) {
                $onInstall[] = "i0";
            };

            $directory->root = function () {return "/root";};
            $directory->cache = function () {return "/root/c";};
            $directory->task = function () {return "/tmp/task";};
            $directory->state = function () {return "/tmp/state";};
            $directory->other = function () {return "/tmp/other";};
            $directory->packages = function () {return "/tmp/packages";};
            $dir->filenames = function (string $dir) use (&$filenames) {
                $filenames[] = $dir;

                // internal root
                if ($dir === "/root")
                    return ["c", "d0", "f0"];

                // external root
                if ($dir === "/tmp/state")
                    return ["c", "f1"];

                if ($dir === "/root/c")
                    return ["f2"];

                if ($dir == "/root/d0")
                    return ["f3"];

                return [];
            };

            $dir->is = function (string $dir) use (&$is) {
                $is[] = $dir;

                return $dir == "/root/c" ||
                    $dir == "/root/d0" ||
                    $dir == "/tmp/state/c";
            };

            $directory->delete = function (string $file) use (&$delete) {
                $delete[] = $file;
            };

            $directory->rename = function (string $from, string $to) use (&$rename) {
                $rename[] = "$from->$to";
            };

            $file->exists = function (string $file) use (&$exists) {
                $exists[] = $file;

                return $file == "/root/c";
            };

            $task->execute();

            if ($onDelete != ["i0"] ||
                $onInstall != ["i0"] ||
                $delete != [
                    "/root/c/f2",
                    "/root/d0/f3",
                    "/root/d0",
                    "/root/f0"] ||
                $rename != [
                    "/tmp/state/f1->/root/f1"] ||
                $filenames != [
                    "/root",
                    "/root/c",
                    "/root/d0",
                    "/tmp/state",
                    "/tmp/state/c"] ||
                $is != [
                    "/root/c",
                    "/root/c/f2",
                    "/root/d0",
                    "/root/d0/f3",
                    "/root/f0",
                    "/tmp/state/c",
                    "/tmp/state/f1",
                ])
                $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }

    public function testShiftRecursiveCache(): void
    {
        try {
            $bus = new BusMock;
            $group = new GroupMock;
            $directory = new DirectoryMock;
            $file = new FileMock;
            $dir = new DirMock;
            $task = new Shift(
                box: $this->box,
                bus: $bus,
                group: $group,
                log: $this->log,
                directory: $directory,
                file: $file,
                dir: $dir,
                config: []
            );

            $filenames =
            $delete =
            $exists =
            $create =
            $onDelete =
            $onInstall =
            $rename =
            $is = [];
            $group->hasDownloadable = true;
            $group->internalMetas["i0"] = new InternalMetadataMock(
                InternalCategory::OBSOLETE, [
                "structure" => [
                    "cache" => "/c0"
                ]
            ]);
            $group->internalMetas["i0"]->delete = function () use (&$onDelete) {
                $onDelete[] = "i0";
            };
            $group->internalRoot = $group->internalMetas["i0"];
            $group->externalMetas["i0"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE, [
                "structure" => [
                    "cache" => "/c1"
                ]
            ]);

            $group->externalRoot = $group->externalMetas["i0"];
            $group->externalMetas["i0"]->install = function () use (&$onInstall) {
                $onInstall[] = "i0";
            };

            $directory->root = function () {return "/root";};
            $directory->cache = function () {return "/root/c0";};
            $directory->task = function () {return "/tmp/task";};
            $directory->state = function () {return "/tmp/state";};
            $directory->other = function () {return "/tmp/other";};
            $directory->packages = function () {return "/tmp/packages";};
            $dir->filenames = function (string $dir) use (&$filenames) {
                $filenames[] = $dir;

                // internal root
                if ($dir === "/root")
                    return ["c0", "f0"];

                // external root
                if ($dir === "/tmp/state")
                    return ["c1", "f1"];

                if ($dir === "/root/c0")
                    return ["log", "f2"];

                if ($dir === "/tmp/state/c1")
                    return ["f3"];

                if ($dir == "/root/c0/log")
                    return ["f4"];

                return [];
            };

            $dir->is = function (string $dir) use (&$is) {
                $is[] = $dir;

                return $dir == "/root/c0" ||
                    $dir == "/root/c0/log" ||
                    $dir == "/tmp/state/c1";
            };

            $directory->delete = function (string $file) use (&$delete) {
                $delete[] = $file;
            };

            $directory->create = function (string $file) use (&$create) {
              $create[] = $file;
            };

            $directory->rename = function (string $from, string $to) use (&$rename) {
                $rename[] = "$from->$to";
            };

            $file->exists = function (string $file) use (&$exists) {
                $exists[] = $file;

                return $file == "/root/c1";
            };

            $task->execute();

            if ($onDelete != ["i0"] ||
                $onInstall != ["i0"] ||
                $delete != [
                    "/root/c0/f2",
                    "/root/f0",
                    "/root/c0"] ||
                $create != ["/root/c1"] ||
                $rename != [
                    "/root/c0->/root/c1",
                    "/tmp/state/c1/f3->/root/c1/f3",
                    "/tmp/state/f1->/root/f1"] ||
                $filenames != [
                    "/root",
                    "/root/c0",
                    "/tmp/state",
                    "/tmp/state/c1"] ||
                $is != [
                    "/root/c0",
                    "/root/c0/log",
                    "/root/c0/f2",
                    "/root/f0",
                    "/tmp/state/c1",
                    "/tmp/state/c1/f3",
                    "/tmp/state/f1",
                ])
                $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }

    public function testShiftRecursiveCacheIntersection(): void
    {
        try {
            $bus = new BusMock;
            $group = new GroupMock;
            $directory = new DirectoryMock;
            $file = new FileMock;
            $dir = new DirMock;
            $task = new Shift(
                box: $this->box,
                bus: $bus,
                group: $group,
                log: $this->log,
                directory: $directory,
                file: $file,
                dir: $dir,
                config: []
            );

            $filenames =
            $delete =
            $exists =
            $create =
            $onDelete =
            $onInstall =
            $rename =
            $is = [];
            $group->hasDownloadable = true;
            $group->internalMetas["i0"] = new InternalMetadataMock(
                InternalCategory::OBSOLETE, [
                "structure" => [
                    "cache" => "/c"
                ]
            ]);
            $group->internalMetas["i0"]->delete = function () use (&$onDelete) {
                $onDelete[] = "i0";
            };
            $group->internalRoot = $group->internalMetas["i0"];
            $group->externalMetas["i0"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE, [
                "structure" => [
                    "cache" => "/c/new"
                ]
            ]);

            $group->externalRoot = $group->externalMetas["i0"];
            $group->externalMetas["i0"]->install = function () use (&$onInstall) {
                $onInstall[] = "i0";
            };
            $directory->root = function () {return "/root";};
            $directory->cache = function () {return "/root/c";};
            $directory->task = function () {return "/tmp/task";};
            $directory->state = function () {return "/tmp/state";};
            $directory->other = function () {return "/tmp/other";};
            $directory->packages = function () {return "/tmp/packages";};
            $dir->filenames = function (string $dir) use (&$filenames) {
                $filenames[] = $dir;

                // internal root
                if ($dir === "/root")
                    return ["c", "f0"];

                // external root
                if ($dir === "/tmp/state")
                    return ["c", "f1"];

                if ($dir === "/root/c")
                    return ["log", "f2"];

                if ($dir === "/tmp/state/c")
                    return ["f3"];

                if ($dir == "/root/c/log")
                    return ["f4"];

                return [];
            };

            $dir->is = function (string $dir) use (&$is) {
                $is[] = $dir;
                return $dir == "/root/c" ||
                    $dir == "/root/c/log" ||
                    $dir == "/tmp/state/c";
            };

            $directory->delete = function (string $file) use (&$delete) {
                $delete[] = $file;
            };

            $directory->create = function (string $file) use (&$create) {
                $create[] = $file;
            };

            $directory->rename = function (string $from, string $to) use (&$rename) {
                $rename[] = "$from->$to";
            };

            $file->exists = function (string $file) use (&$exists) {
                $exists[] = $file;
                return $file == "/root/c";
            };

            $task->execute();

            if ($onDelete != ["i0"] ||
                $onInstall != ["i0"] ||
                $delete != [
                    "/root/c/f2",
                    "/root/f0",
                    "/root/c"] ||
                $create != ["/root/c/new"] ||
                $rename != [
                    "/root/c->/root/c-",
                    "/root/c-->/root/c/new",
                    "/tmp/state/c/f3->/root/c/f3",
                    "/tmp/state/f1->/root/f1"] ||
                $filenames != [
                    "/root",
                    "/root/c",
                    "/tmp/state",
                    "/tmp/state/c"] ||
                $is != [
                    "/root/c",
                    "/root/c/log",
                    "/root/c/f2",
                    "/root/f0",
                    "/tmp/state/c",
                    "/tmp/state/c/f3",
                    "/tmp/state/f1",
                ])
                $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }

    public function testShiftNested(): void
    {
        try {
            $bus = new BusMock;
            $group = new GroupMock;
            $directory = new DirectoryMock;
            $file = new FileMock;
            $dir = new DirMock;
            $task = new Shift(
                box: $this->box,
                bus: $bus,
                group: $group,
                log: $this->log,
                directory: $directory,
                file: $file,
                dir: $dir,
                config: []
            );

            $filenames =
            $delete =
            $exists =
            $create =
            $rename =
            $copy =
            $onUpdate =
            $onDelete =
            $onInstall =
            $isFile =
            $isDir = [];

            $group->hasDownloadable = true;
            $group->internalMetas["i0"] = new InternalMetadataMock(
                InternalCategory::RECYCLABLE, [
                "dir" => "",
                "source" => "/root",
                "structure" => [
                    "cache" => "/c",
                    "extensions" => [
                        "/ex0"
                    ],
                    "states" => [
                        "/st0"
                    ]
                ]
            ]);

            $group->internalMetas["i0"]->update = function () use (&$onUpdate) {
                $onUpdate[] = "i0";
            };

            $group->internalRoot = $group->internalMetas["i0"];
            $group->internalMetas["i2"] = new InternalMetadataMock(
                InternalCategory::MOVABLE, [
                "source" => "/si2",
            ]);

            $group->internalMetas["i2"]->delete = function () use (&$onDelete) {
                $onDelete[] = "i2";
            };

            $group->internalMetas["i3"] = new InternalMetadataMock(
                InternalCategory::OBSOLETE, [
                "source" => "/si3",
            ]);

            $group->internalMetas["i3"]->delete = function () use (&$onDelete) {
                $onDelete[] = "i3";
            };

            $group->externalMetas["i1"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE, [
                "dir" => "/di1"
            ]);

            $group->externalMetas["i1"]->install = function () use (&$onInstall) {
                $onInstall[] = "i1";
            };

            $group->externalMetas["i2"] = new ExternalMetadataMock(
                ExternalCategory::REDUNDANT, [
                "dir" => "/di2"
            ]);

            $group->externalMetas["i2"]->install = function () use (&$onInstall) {
                $onInstall[] = "i2";
            };

            $directory->root = function () {return "/root";};
            $directory->cache = function () {return "/root/c";};
            $directory->task = function () {return "/tmp/task";};
            $directory->state = function () {return "/tmp/state";};
            $directory->other = function () {return "/tmp/other";};
            $directory->packages = function () {return "/tmp/packages";};
            $dir->filenames = function (string $dir) use (&$filenames) {
                $filenames[] = $dir;

                // internal root
                if ($dir === "/root")
                    return ["c", "f0"];

                if ($dir === "/root/c")
                    return ["log", "f1"];

                if ($dir == "/root/c/log")
                    return ["f2"];

                // external root
                if ($dir === "/tmp/state")
                    return ["c", "f3"];

                if ($dir === "/tmp/state/c")
                    return ["f4"];

                if ($dir === "/si2")
                    return ["f5"];

                if ($dir === "/si3")
                    return ["f6"];

                if ($dir === "/tmp/state/di1")
                    return ["d0", "f7"];

                if ($dir === "/tmp/state/di1/d0")
                    return ["f8"];

                return [];
            };

            $dir->is = function (string $dir) use (&$isDir) {
                $isDir[] = $dir;
                return $dir == "/root/c" ||
                    $dir == "/root/c/log" ||
                    $dir == "/tmp/state/ex0" ||
                    $dir == "/tmp/state/st0" ||
                    $dir == "/tmp/state/di1/d0" ||
                    $dir == "/tmp/state/c";
            };

            $directory->delete = function (string $file) use (&$delete) {
                $delete[] = $file;
            };

            $directory->create = function (string $file) use (&$create) {
                $create[] = $file;
            };

            $directory->rename = function (string $from, string $to) use (&$rename) {
                $rename[] = "$from->$to";
            };

            $directory->copy = function (string $from, string $to) use (&$copy) {
                $copy[] = "$from->$to";
            };

            $file->exists = function (string $file) use (&$exists) {
                $exists[] = $file;
                return $file == "/root/c";
            };

            $file->is = function (string $file) use (&$isFile) {
                $isFile[] = $file;

                return $file == "/tmp/state/c/f4";
            };
            $task->execute();

            if ($onUpdate != ["i0"] ||
                $onDelete != ["i2", "i3"] ||
                $onInstall != ["i1", "i2"] ||
                $delete != [
                    "/root/c/f1",
                    "/root/ex0",
                    "/root/st0",
                    "/si2/f5",
                    "/si2",
                    "/si3/f6",
                    "/si3",] ||
                $create != [
                    "/root/di1",
                    "/root/di1/d0",
                    "/root/di2"] ||
                $rename != [
                    "/tmp/state/ex0->/root/ex0",
                    "/tmp/state/st0->/root/st0",
                    "/tmp/state/di1/d0->/root/di1/d0",
                    "/tmp/state/di1/f7->/root/di1/f7"] ||
                $filenames != [
                    "/root/c",
                    "/tmp/state/c",
                    "/si2",
                    "/si3",
                    "/tmp/state/di1",
                    "/tmp/state/di2"] ||
                $exists != [
                    "/root/c",
                    "/root/di1",
                    "/root/di1/d0",
                    "/root/di2",] ||
                $copy != ["/tmp/state/c/f4->/root/c/f4"] ||
                $isFile != ["/tmp/state/c/f4"] ||
                $isDir != [
                    "/root/c/log",
                    "/root/c/f1",
                    "/tmp/state/ex0",
                    "/tmp/state/st0",
                    "/si2/f5",
                    "/si3/f6",
                    "/tmp/state/di1/d0",
                    "/tmp/state/di1/f7"
                ])
                $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }

    public function testShiftRecursiveWithExecutedFiles(): void
    {
        try {
            $bus = new BusMock;
            $group = new GroupMock;
            $directory = new DirectoryMock;
            $file = new FileMock;
            $dir = new DirMock;
            $task = new Shift(
                box: $this->box,
                bus: $bus,
                group: $group,
                log: $this->log,
                directory: $directory,
                file: $file,
                dir: $dir,
                config: []
            );

            $filenames =
            $delete =
            $rename =
            $exists =
            $create =
            $onDelete =
            $onInstall =
            $get =
            $put =
            $isFile =
            $newRoot =
            $copy =
            $isDir = [];
            $group->hasDownloadable = true;
            $root = dirname(__DIR__, 3);
            $group->internalMetas["valvoid/fusion"] = new InternalMetadataMock(
                InternalCategory::OBSOLETE, [
                "source" => $root,
                "dir" => "",
                "structure" => [
                    "cache" => "/c",
                    "sources" => []
                ]
            ]);

            $group->internalMetas["valvoid/fusion"]->delete = function () use (&$onDelete) {
                $onDelete[] = "i0";
            };

            $group->internalRoot = $group->internalMetas["valvoid/fusion"];
            $group->externalMetas["valvoid/fusion"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE, [
                "dir" => "",
                "structure" => [
                    "cache" => "/c"
                ]
            ]);

            $group->externalRoot = $group->externalMetas["valvoid/fusion"];
            $group->externalMetas["valvoid/fusion"]->install = function () use (&$onInstall) {
                $onInstall[] = "i0";
            };

            $bus->addReceiver(self::class, function (Root $root) use (&$newRoot) {
                $newRoot[] = $root->getDir();
            }, Root::class);
            $directory->root = function () use ($root) {return $root;};
            $directory->cache = function () use ($root) {return "$root/c";};
            $directory->task = function () {return "/tmp/task";};
            $directory->state = function () {return "/tmp/state";};
            $directory->other = function () {return "/tmp/other";};
            $directory->packages = function () {return "/tmp/packages";};
            $dir->filenames = function (string $dir) use (&$filenames, $root) {
                $filenames[] = $dir;

                // internal root
                if ($dir === "$root")
                    return ["c", "d0", "f0", "fusion"];

                // external root
                if ($dir === "/tmp/state")
                    return ["c", "f1", "fusion"];

                if ($dir === "$root/c")
                    return ["f2"];

                if ($dir == "$root/d0")
                    return ["f3"];

                return [];
            };

            $dir->is = function (string $dir) use (&$isDir, $root) {
                $isDir[] = $dir;

                return $dir == "$root/c" ||
                    $dir == "$root/d0" ||
                    $dir == "/tmp/state/c";
            };

            $file->is = function (string $file) use (&$isFile, $root) {
                $isFile[] = $file;

                return $file == "$root/c/f2" ||
                    $file == "$root/fusion" ||
                    $file == "$root/f0" ||
                    $file == "$root/d0/f3";
            };

            $directory->delete = function (string $file) use (&$delete) {
                $delete[] = $file;
            };

            $directory->create = function (string $file) use (&$create) {
                $create[] = $file;
            };

            $directory->rename = function (string $from, string $to) use (&$rename) {
                $rename[] = "$from->$to";
            };

            $directory->copy = function (string $from, string $to) use (&$copy) {
                $copy[] = "$from->$to";
            };

            $file->exists = function (string $file) use (&$exists, $root) {
                $exists[] = $file;
                return $file == "$root/c";
            };

            $file->put = function (string $file, mixed $data) use (&$put) {
                $put[] = "$file($data)";
                return 1;
            };

            $file->get = function (string $file) use (&$get) {
                $get[] = $file;
                return "###";
            };

            $task->execute();
            $bus->removeReceiver(self::class, Root::class);

            if ($newRoot != ["/tmp/other/valvoid/fusion"] ||
                $onDelete != ["i0"] ||
                $onInstall != ["i0"] ||
                $create != [
                    "/tmp/other/valvoid/fusion",
                    "/tmp/other/valvoid/fusion/c",
                    "/tmp/other/valvoid/fusion/d0"] ||
                $copy != [
                    "$root/c/f2->/tmp/other/valvoid/fusion/c/f2",
                    "$root/d0/f3->/tmp/other/valvoid/fusion/d0/f3",
                    "$root/f0->/tmp/other/valvoid/fusion/f0",
                    "$root/fusion->/tmp/other/valvoid/fusion/fusion"] ||
                $delete != [
                    "$root/c/f2",
                    "$root/d0/f3",
                    "$root/d0",
                    "$root/f0"] ||
                $rename != ["/tmp/state/f1->$root/f1"] ||
                $exists != ["$root/c"] ||
                $get != ["/tmp/state/fusion"] ||
                $put != [
                    "$root/fusion()",
                    "$root/fusion(###)"] ||
                $filenames != [
                    "$root",
                    "$root/c",
                    "$root/d0",
                    "$root",
                    "$root/c",
                    "$root/d0",
                    "/tmp/state",
                    "/tmp/state/c"] ||
                $isFile != [
                    "$root/c",
                    "$root/c/f2",
                    "$root/d0",
                    "$root/d0/f3",
                    "$root/f0",
                    "$root/fusion"] ||
                $isDir != [
                    "$root/c",
                    "$root/c/f2",
                    "$root/d0",
                    "$root/d0/f3",
                    "$root/f0",
                    "$root/fusion",
                    "/tmp/state/c",
                    "/tmp/state/f1",
                    "/tmp/state/fusion"
                ])
                $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }

    public function testShiftNestedWithExecutedFiles(): void
    {
        try {
            $bus = new BusMock;
            $group = new GroupMock;
            $directory = new DirectoryMock;
            $file = new FileMock;
            $dir = new DirMock;
            $task = new Shift(
                box: $this->box,
                bus: $bus,
                group: $group,
                log: $this->log,
                directory: $directory,
                file: $file,
                dir: $dir,
                config: []
            );

            $filenames =
            $delete =
            $rename =
            $exists =
            $create =
            $onDelete =
            $onUpdate =
            $onInstall =
            $get =
            $put =
            $isFile =
            $newRoot =
            $copy =
            $isDir = [];
            $group->hasDownloadable = true;
            $root = dirname(__DIR__, 3);
            $group->internalMetas["i0"] = new InternalMetadataMock(
                InternalCategory::RECYCLABLE, [
                "source" => $root,
                "dir" => "",
                "structure" => [
                    "cache" => "/c",
                    "extensions" => [],
                    "states" => []
                ]
            ]);
            $group->internalMetas["i0"]->update = function () use (&$onUpdate) {
                $onUpdate[] = "i0";
            };
            $group->internalMetas["valvoid/fusion"] = new InternalMetadataMock(
                InternalCategory::OBSOLETE, [
                "source" => "$root/deps/valvoid/fusion",
                "dir" => "/deps/valvoid/fusion"
            ]);

            $group->internalMetas["valvoid/fusion"]->delete = function () use (&$onDelete) {
                $onDelete[] = "valvoid/fusion";
            };

            $group->internalRoot = $group->internalMetas["i0"];
            $group->externalMetas["valvoid/fusion"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE, [
                "dir" => "/deps/valvoid/fusion"
            ]);

            $group->externalMetas["valvoid/fusion"]->install = function () use (&$onInstall) {
                $onInstall[] = "valvoid/fusion";
            };

            $bus->addReceiver(self::class, function (Root $root) use (&$newRoot) {
                $newRoot[] = $root->getDir();
            }, Root::class);
            $directory->root = function () use ($root) {return $root;};
            $directory->cache = function () use ($root) {return "$root/c";};
            $directory->task = function () {return "/tmp/task";};
            $directory->state = function () {return "/tmp/state";};
            $directory->other = function () {return "/tmp/other";};
            $directory->packages = function () {return "/tmp/packages";};
            $dir->filenames = function (string $dir) use (&$filenames, $root) {
                $filenames[] = $dir;

                // external cache
                if ($dir === "/tmp/state/c")
                    return ["f1"];

                // internal cache
                if ($dir === "$root/c")
                    return ["f2"];

                if ($dir == "$root/deps/valvoid/fusion")
                    return ["d0", "f3", "fusion"];

                if ($dir == "$root/deps/valvoid/fusion/d0")
                    return ["f4"];

                if ($dir == "/tmp/state/deps/valvoid/fusion")
                    return ["d1", "f5", "fusion"];

                if ($dir == "/tmp/state/deps/valvoid/fusion/d1")
                    return ["f6"];

                return [];
            };

            $dir->is = function (string $dir) use (&$isDir, $root) {
                $isDir[] = $dir;
                return $dir == "$root/c" ||
                    $dir == "$root/deps/valovid/fusion" ||
                    $dir == "$root/deps/valovid/fusion/d0" ||
                    $dir == "/tmp/state/deps/valvoid/fusion" ||
                    $dir == "/tmp/state/deps/valvoid/fusion/d1" ||
                    $dir == "/tmp/state/c";
            };

            $file->is = function (string $file) use (&$isFile, $root) {
                $isFile[] = $file;

                return $file == "$root/c/f2" ||
                    $file == "$root/deps/valvoid/fusion/fusion" ||
                    $file == "$root/deps/valvoid/fusion/f3" ||
                    $file == "$root/deps/valvoid/fusion/d0/f4" ||
                    $file == "/tmp/state/c/f1";
            };

            $directory->delete = function (string $file) use (&$delete) {
                $delete[] = $file;
            };

            $directory->create = function (string $file) use (&$create) {
                $create[] = $file;
            };

            $directory->rename = function (string $from, string $to) use (&$rename) {
                $rename[] = "$from->$to";
            };

            $directory->copy = function (string $from, string $to) use (&$copy) {
                $copy[] = "$from->$to";
            };

            $file->exists = function (string $file) use (&$exists, $root) {
                $exists[] = $file;
                return $file == "$root/c" ||
                    $file == "$root/deps/valvoid/fusion";
            };

            $file->put = function (string $file, mixed $data) use (&$put) {
                $put[] = "$file($data)";
                return 1;
            };

            $file->get = function (string $file) use (&$get) {
                $get[] = $file;
                return "###";
            };

            $task->execute();
            $bus->removeReceiver(self::class, Root::class);

            // refresh root cache:
            //  - delete current root cache
            //  - copy new from state

            // keep Fusion alive:
            //  - copy internal obsolete to temp and notify new root dir
            //  - delete internal, reset content of executed files
            //  - rename new from state, copy content into executed files

            if ($newRoot != ["/tmp/other/valvoid/fusion"] ||
                $onDelete != ["valvoid/fusion"] ||
                $onInstall != ["valvoid/fusion"] ||
                $create != [
                    "/tmp/other/valvoid/fusion",
                    "/tmp/other/valvoid/fusion/d0",
                    "$root/deps/valvoid/fusion/d1"] ||
                $copy != [
                    "/tmp/state/c/f1->$root/c/f1",
                    "$root/deps/valvoid/fusion/d0/f4->/tmp/other/valvoid/fusion/d0/f4",
                    "$root/deps/valvoid/fusion/f3->/tmp/other/valvoid/fusion/f3",
                    "$root/deps/valvoid/fusion/fusion->/tmp/other/valvoid/fusion/fusion"] ||
                $delete != [
                    "$root/c/f2",
                    "$root/deps/valvoid/fusion/d0",
                    "$root/deps/valvoid/fusion/f3"] ||
                $rename != [
                    "/tmp/state/deps/valvoid/fusion/d1->$root/deps/valvoid/fusion/d1",
                    "/tmp/state/deps/valvoid/fusion/f5->$root/deps/valvoid/fusion/f5"] ||
                $exists != [
                    "$root/c",
                    "$root/deps/valvoid/fusion",
                    "$root/deps/valvoid/fusion/d1"] ||
                $get != ["/tmp/state/deps/valvoid/fusion/fusion"] ||
                $put != [
                    "$root/deps/valvoid/fusion/fusion()",
                    "$root/deps/valvoid/fusion/fusion(###)"] ||
                $filenames != [
                    "$root/c",
                    "/tmp/state/c",
                    "$root/deps/valvoid/fusion",
                    "$root/deps/valvoid/fusion/d0",
                    "$root/deps/valvoid/fusion",
                    "/tmp/state/deps/valvoid/fusion"] ||
                $isFile != [
                    "/tmp/state/c/f1",
                    "$root/deps/valvoid/fusion/d0",
                    "$root/deps/valvoid/fusion/d0/f4",
                    "$root/deps/valvoid/fusion/f3",
                    "$root/deps/valvoid/fusion/fusion"] ||
                $isDir != [
                    "$root/c/f2",
                    "$root/deps/valvoid/fusion/d0",
                    "$root/deps/valvoid/fusion/f3",
                    "$root/deps/valvoid/fusion/fusion",
                    "/tmp/state/deps/valvoid/fusion/d1",
                    "/tmp/state/deps/valvoid/fusion/f5",
                    "/tmp/state/deps/valvoid/fusion/fusion"])
                $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }
}