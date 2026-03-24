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

namespace Valvoid\Fusion\Tests\Units\Tasks\Shift;

use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Cache;
use Valvoid\Fusion\Bus\Events\Root;
use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Metadata\External\External;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tasks\Shift\Shift;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Fusion\Wrappers\Dir as DirWrapper;
use Valvoid\Fusion\Wrappers\System;
use Valvoid\Reflex\Test\Wrapper;

class ShiftTest extends Wrapper
{
    public function testRefresh(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $group = $this->createMock(Group::class);
        $log = $this->createMock(Log::class);
        $directory = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $dir = $this->createMock(DirWrapper::class);
        $system = $this->createMock(System::class);
        $internal = $this->createMock(Internal::class);
        $task = new Shift(
            box: $box,
            bus: $bus,
            group: $group,
            log: $log,
            directory: $directory,
            file: $file,
            dir: $dir,
            system: $system,
            config: []
        );

        $log->fake("info")
            ->return(null);

        $directory->fake("getRootDir")
            ->return("/root")
            ->fake("delete")
            ->expect(file: "/si1")
            ->fake("clear")
            ->expect(dir: "/root", path: "/di1");

        $group->fake("hasDownloadable")
            ->return(false)
            ->fake("getInternalMetas")
            ->return([
                "i0" => $internal,
                "i1" => $internal
            ]);

        $internal->fake("getCategory")
            ->return(InternalCategory::RECYCLABLE) // i0
            ->return(InternalCategory::OBSOLETE) // i1
            ->fake("onUpdate")  // i0
            ->return(true)
            ->fake("onDelete")  // i1
            ->return(true)
            ->fake("getSource")
            ->return("/si1")
            ->fake("getDir")
            ->return("/di1");

        $task->execute();
    }

    public function testShiftRecursive(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $group = $this->createMock(Group::class);
        $log = $this->createMock(Log::class);
        $directory = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $dir = $this->createMock(DirWrapper::class);
        $system = $this->createMock(System::class);
        $internal = $this->createMock(Internal::class);
        $external = $this->createMock(External::class);
        $content = $this->createStub(Content::class);
        $task = new Shift(
            box: $box,
            bus: $bus,
            group: $group,
            log: $log,
            directory: $directory,
            file: $file,
            dir: $dir,
            system: $system,
            config: []
        );

        $log->fake("info")
            ->return(null)
            ->repeat(1);

        $directory->fake("getRootDir")
            ->return("/root")
            ->fake("getStateDir")
            ->return("/tmp/state")
            ->fake("getStatefulDir") // legacy cache
            ->return("/root/c")
            ->fake("getPackagesDir")
            ->return("/tmp/packages")
            ->fake("getTaskDir")
            ->return("/tmp/task")
            ->fake("getOtherDir")
            ->return("/tmp/other")
            ->fake("delete")
            ->expect(file: "/root/c/f2")
            ->expect(file: "/root/d0/f3")
            ->expect(file: "/root/d0")
            ->expect(file: "/root/f0")
            ->expect(file: "/tmp/state/f1")
            ->fake("copy")
            ->expect(from: "/tmp/state/f1", to: "/root/f1");

        $external->fake("getCategory")
            ->return(ExternalCategory::DOWNLOADABLE)
            ->repeat(1)
            ->fake("getStatefulPath")
            ->return("/c")
            ->fake("onInstall")
            ->return(true)
            ->fake("getContent")
            ->return(["###"]);

        $box->fake("get")
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->return($content);

        $internal->fake("getCategory")
            ->return(InternalCategory::OBSOLETE)
            ->repeat(1)
            ->fake("getStatefulPath")
            ->return("/c")
            ->fake("onDelete")
            ->return(true);

        $group->fake("hasDownloadable")
            ->return(true)
            ->fake("getExternalRootMetadata")
            ->return($external)
            ->fake("getInternalRootMetadata")
            ->return($internal)
            ->fake("getExternalMetas")
            ->return(["i0" => $external])
            ->fake("getInternalMetas")
            ->return(["i0" => $internal]);

        $dir->fake("getFilenames")
            ->expect(dir: "/root")
            ->return(["c", "d0", "f0"])
            ->expect(dir: "/root/c")
            ->return(["f2"])
            ->expect(dir: "/root/d0")
            ->return(["f3"])
            ->expect(dir: "/tmp/state")
            ->return(["c", "f1"])
            ->expect(dir: "/tmp/state/c")
            ->return([])
            ->fake("is")
            ->expect(dir: "/root/c")
            ->return(true)
            ->expect(dir: "/root/c/f2")
            ->return(false)
            ->expect(dir: "/root/d0")
            ->return(true)
            ->expect(dir: "/root/d0/f3")
            ->return(false)
            ->expect(dir: "/root/f0")
            ->expect(dir: "/root/c") // shift dir
            ->return(true)
            ->expect(dir: "/tmp/state/c")
            ->expect(dir: "/tmp/state/f1")
            ->return(false);

        $file->fake("exists")
            ->expect(file: "/root/c")
            ->return(true);

        $task->execute();
    }

    public function testShiftRecursiveCache(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $group = $this->createMock(Group::class);
        $log = $this->createMock(Log::class);
        $directory = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $dir = $this->createMock(DirWrapper::class);
        $system = $this->createMock(System::class);
        $internal = $this->createMock(Internal::class);
        $external = $this->createMock(External::class);
        $content = $this->createStub(Content::class);
        $cache = $this->createStub(Cache::class);
        $task = new Shift(
            box: $box,
            bus: $bus,
            group: $group,
            log: $log,
            directory: $directory,
            file: $file,
            dir: $dir,
            system: $system,
            config: []
        );

        $log->fake("info")
            ->return(null)
            ->repeat(1);

        $directory->fake("getRootDir")
            ->return("/root")
            ->fake("getStateDir")
            ->return("/tmp/state")
            ->repeat(1)
            ->fake("getStatefulDir") // legacy cache
            ->return("/root/c0")
            ->fake("getPackagesDir")
            ->return("/tmp/packages")
            ->fake("getTaskDir")
            ->return("/tmp/task")
            ->fake("getOtherDir")
            ->return("/tmp/other")
            ->fake("delete")
            ->expect(file: "/root/c0/f2")
            ->expect(file: "/root/f0")
            ->expect(file: "/root/c0")
            ->expect(file: "/root/c0")
            ->expect(file: "/tmp/state/c1/f3")
            ->expect(file: "/tmp/state/f1")
            ->fake("createDir")
            ->expect(dir: "/root/c1")
            ->fake("copy")
            ->expect(from: "/root/c0/f2", to: "/root/c1/f2")
            ->expect(from: "/tmp/state/c1/f3", to: "/root/c1/f3")
            ->expect(from: "/tmp/state/f1", to: "/root/f1");

        $group->fake("hasDownloadable")
            ->return(true)
            ->fake("getExternalRootMetadata")
            ->return($external)
            ->fake("getInternalRootMetadata")
            ->return($internal)
            ->fake("getExternalMetas")
            ->return(["i0" => $external])
            ->fake("getInternalMetas")
            ->return(["i0" => $internal]);

        $external->fake("getCategory")
            ->return(ExternalCategory::DOWNLOADABLE)
            ->repeat(1)
            ->fake("getStatefulPath")
            ->return("/c1")
            ->fake("onInstall")
            ->return(true)
            ->fake("getContent")
            ->return(["###"]);

        $box->fake("get")
            ->expect(class: Cache::class, arguments: ["dir" => "/root/c1"])
            ->return($cache)
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->return($content);

        $bus->fake("broadcast")
            ->expect(event: $cache);

        $internal->fake("getCategory")
            ->return(InternalCategory::OBSOLETE)
            ->repeat(1)
            ->fake("getStatefulPath")
            ->return("/c0")
            ->fake("onDelete")
            ->return(true);

        $dir->fake("getFilenames")
            ->expect(dir: "/root")
            ->return(["c0", "f0"])
            ->expect(dir: "/root/c0")
            ->return(["log", "f2"])
            ->repeat(1)
            ->expect(dir: "/tmp/state")
            ->return(["c1", "f1"])
            ->expect(dir: "/tmp/state/c1")
            ->return(["f3"])
            ->fake("is")
            ->expect(dir: "/root/c0")
            ->return(true)
            ->expect(dir: "/root/c0/log")
            ->expect(dir: "/root/c0/f2")
            ->return(false)
            ->expect(dir: "/root/f0")
            ->expect(dir: "/root/c0") // compare cache dirs
            ->return(true)
            ->expect(dir: "/tmp/state/c1")
            ->return(true)
            ->expect(dir: "/tmp/state/c1/f3")
            ->return(false)
            ->expect(dir: "/tmp/state/f1");

        $file->fake("is")
            ->expect(file: "/root/c0/log") // copy to new cache
            ->return(false)
            ->expect(file: "/root/c0/f2")
            ->return(true)
            ->fake("exists")
            ->expect(file: "/root/c1")
            ->return(true);

        $task->execute();
    }

    public function testShiftRecursiveCacheIntersection(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $group = $this->createMock(Group::class);
        $log = $this->createMock(Log::class);
        $directory = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $dir = $this->createMock(DirWrapper::class);
        $system = $this->createMock(System::class);
        $internal = $this->createMock(Internal::class);
        $external = $this->createMock(External::class);
        $content = $this->createStub(Content::class);
        $cache = $this->createStub(Cache::class);
        $task = new Shift(
            box: $box,
            bus: $bus,
            group: $group,
            log: $log,
            directory: $directory,
            file: $file,
            dir: $dir,
            system: $system,
            config: []
        );

        $log->fake("info")
            ->return(null)
            ->repeat(1);

        $directory->fake("getRootDir")
            ->return("/root")
            ->fake("getStateDir")
            ->return("/tmp/state")
            ->repeat(1)
            ->fake("getStatefulDir") // legacy cache
            ->return("/root/c")
            ->fake("getPackagesDir")
            ->return("/tmp/packages")
            ->fake("getTaskDir")
            ->return("/tmp/task")
            ->fake("getOtherDir")
            ->return("/tmp/other")
            ->fake("delete")
            ->expect(file: "/root/c/f2")
            ->expect(file: "/root/f0")
            ->expect(file: "/root/c")
            ->expect(file: "/root/c")
            ->expect(file: "/root/c-")
            ->expect(file: "/tmp/state/c/f3")
            ->expect(file: "/tmp/state/f1")
            ->fake("createDir")
            ->expect(dir: "/root/c/new")
            ->fake("copy")
            ->expect(from: "/root/c/f2", to: "/root/c-/f2")
            ->expect(from: "/tmp/state/c/f3", to: "/root/c/f3")
            ->expect(from: "/tmp/state/f1", to: "/root/f1");

        $group->fake("hasDownloadable")
            ->return(true)
            ->fake("getExternalRootMetadata")
            ->return($external)
            ->fake("getInternalRootMetadata")
            ->return($internal)
            ->fake("getExternalMetas")
            ->return(["i0" => $external])
            ->fake("getInternalMetas")
            ->return(["i0" => $internal]);

        $external->fake("getCategory")
            ->return(ExternalCategory::DOWNLOADABLE)
            ->repeat(1)
            ->fake("getStatefulPath")
            ->return("/c/new")
            ->fake("onInstall")
            ->return(true)
            ->fake("getContent")
            ->return(["###"]);

        $box->fake("get")
            ->expect(class: Cache::class, arguments: ["dir" => "/root/c/new"])
            ->return($cache)
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->return($content);

        $bus->fake("broadcast")
            ->expect(event: $cache);

        $internal->fake("getCategory")
            ->return(InternalCategory::OBSOLETE)
            ->repeat(1)
            ->fake("getStatefulPath")
            ->return("/c")
            ->fake("onDelete")
            ->return(true);

        $dir->fake("getFilenames")
            ->expect(dir: "/root")
            ->return(["c", "f0"])
            ->expect(dir: "/root/c")
            ->return(["log", "f2"])
            ->repeat(1)
            ->expect(dir: "/root/c-")
            ->return([])
            ->expect(dir: "/tmp/state")
            ->return(["c", "f1"])
            ->expect(dir: "/tmp/state/c")
            ->return(["f3"])
            ->fake("is")
            ->expect(dir: "/root/c")
            ->return(true)
            ->expect(dir: "/root/c/log")
            ->expect(dir: "/root/c/f2")
            ->return(false)
            ->expect(dir: "/root/f0")
            ->expect(dir: "/root/c") // compare cache dirs
            ->return(true)
            ->expect(dir: "/tmp/state/c")
            ->return(true)
            ->expect(dir: "/tmp/state/c/f3")
            ->return(false)
            ->expect(dir: "/tmp/state/f1");

        $file->fake("is")
            ->expect(file: "/root/c/log") // copy to new cache
            ->return(false)
            ->expect(file: "/root/c/f2")
            ->return(true)
            ->fake("exists")
            ->expect(file: "/root/c-")
            ->return(false)
            ->expect(file: "/root/c")
            ->return(true);

        $task->execute();
    }

    public function testShiftNested(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $group = $this->createMock(Group::class);
        $log = $this->createMock(Log::class);
        $directory = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $dir = $this->createMock(DirWrapper::class);
        $system = $this->createMock(System::class);
        $internal = $this->createMock(Internal::class);
        $external = $this->createMock(External::class);
        $content = $this->createStub(Content::class);
        $task = new Shift(
            box: $box,
            bus: $bus,
            group: $group,
            log: $log,
            directory: $directory,
            file: $file,
            dir: $dir,
            system: $system,
            config: []
        );

        $log->fake("info")
            ->return(null)
            ->repeat(3);

        $box->fake("get")
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->return($content)
            ->repeat(2);

        $directory->fake("getRootDir")
            ->return("/root")
            ->fake("getStateDir")
            ->return("/tmp/state")
            ->repeat(1)
            ->fake("getPackagesDir")
            ->return("/tmp/packages")
            ->fake("getTaskDir")
            ->return("/tmp/task")
            ->fake("getOtherDir")
            ->return("/tmp/other")
            ->fake("delete")
            ->expect(file: "/root/c/f1")
            ->expect(file: "/root/mt0")
            ->expect(file: "/tmp/state/mt0")
            ->expect(file: "/si2/f5")
            ->expect(file: "/si2")
            ->expect(file: "/si3/f6")
            ->expect(file: "/si3")
            ->expect(file: "/tmp/state/di1/d0")
            ->expect(file: "/tmp/state/di1/f7")
            ->fake("createDir")
            ->expect(dir: "/root/di1")
            ->expect(dir: "/root/di1/d0")
            ->expect(dir: "/root/di2")
            ->fake("copy")
            ->expect(from: "/tmp/state/c/f4", to: "/root/c/f4")
            ->expect(from: "/tmp/state/di1/d0/f8", to: "/root/di1/d0/f8")
            ->expect(from: "/tmp/state/di1/f7", to: "/root/di1/f7");

        $group->fake("hasDownloadable")
            ->return(true)
            ->fake("getExternalRootMetadata")
            ->return(null)
            ->fake("getExternalMetas")
            ->return([
                "i1" => $external,
                "i2" => $external
            ])
            ->fake("getInternalMetas")
            ->return([
                "i0" => $internal,
                "i2" => $internal,
                "i3" => $internal,
            ]);

        $external->fake("getCategory")
            ->return(ExternalCategory::DOWNLOADABLE)
            ->return(ExternalCategory::REDUNDANT) // i2
            ->fake("getDir")
            ->return("/di1")
            ->return("/di2")
            ->fake("onInstall")
            ->return(true)
            ->repeat(1)
            ->fake("getContent")
            ->return(["###"])
            ->repeat(1);

        $internal->fake("getCategory")
            ->return(InternalCategory::RECYCLABLE) // i0
            ->return(InternalCategory::MOVABLE) // i2
            ->return(InternalCategory::OBSOLETE) // i3
            ->return(InternalCategory::MOVABLE) // i2
            ->fake("getDir")
            ->return("") // i0
            ->fake("getSource")
            ->return("/root") // i0
            ->repeat(1)
            ->return("/si2") // i2
            ->return("/si3") // i3
            ->fake("getStatefulPath")
            ->return("/c")
            ->fake("getStructureMutables")
            ->return(["/mt0"])// i0
            ->fake("onUpdate") // i0
            ->return(true)
            ->fake("getContent")  // i0
            ->return(["###"])
            ->fake("onDelete") // i2, i3
            ->return(true)
            ->repeat(1);

        $dir->fake("getFilenames")
            ->expect(dir: "/root/c")
            ->return(["log", "f1"])
            ->expect(dir: "/tmp/state/c")
            ->return(["f4"])
            ->expect(dir: "/tmp/state/mt0")
            ->return([])
            ->expect(dir: "/si2")
            ->return(["f5"])
            ->expect(dir: "/si3")
            ->return(["f6"])
            ->expect(dir: "/tmp/state/di1")
            ->return(["d0", "f7"])
            ->expect(dir: "/tmp/state/di1/d0")
            ->return(["f8"])
            ->expect(dir: "/tmp/state/di2")
            ->return([])

            ->fake("is")
            ->expect(dir: "/root/c/log")
            ->return(true)
            ->expect(dir: "/root/c/f1")
            ->return(false)
            ->expect(dir: "/tmp/state/mt0")
            ->return(true)
            ->expect(dir: "/si2/f5")
            ->return(false)
            ->expect(dir: "/si3/f6")
            ->expect(dir: "/tmp/state/di1/d0")
            ->return(true)
            ->expect(dir: "/tmp/state/di1/f7")
            ->return(false);

        $file->fake("exists")
            ->expect(file: "/root/c")
            ->return(true)
            ->expect(file: "/root/di1")
            ->return(false)
            ->expect(file: "/root/di1/d0")
            ->expect(file: "/root/di2")
            ->fake("is")
            ->expect(file: "/tmp/state/c/f4")
            ->return(true)
            ->expect(file: "/tmp/state/di1/d0/f8");

        $task->execute();
    }

    public function testShiftRecursiveWithExecutedFiles(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $group = $this->createMock(Group::class);
        $log = $this->createMock(Log::class);
        $directory = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $dir = $this->createMock(DirWrapper::class);
        $system = $this->createMock(System::class);
        $internal = $this->createMock(Internal::class);
        $external = $this->createMock(External::class);
        $content = $this->createStub(Content::class);
        $root = $this->createStub(Root::class);
        $task = new Shift(
            box: $box,
            bus: $bus,
            group: $group,
            log: $log,
            directory: $directory,
            file: $file,
            dir: $dir,
            system: $system,
            config: []
        );

        $log->fake("info")
            ->return(null)
            ->repeat(1);

        $directory->fake("getRootDir")
            ->return("/#")
            ->fake("getStatefulDir")
            ->return("/#/c")
            ->fake("getStateDir")
            ->return("/tmp/state")
            ->fake("getPackagesDir")
            ->return("/tmp/packages")
            ->fake("getTaskDir")
            ->return("/tmp/task")
            ->fake("getOtherDir")
            ->return("/tmp/other")
            ->repeat(1)
            ->fake("createDir")
            ->expect(dir: "/tmp/other/valvoid/fusion")
            ->expect(dir: "/tmp/other/valvoid/fusion/c")
            ->expect(dir: "/tmp/other/valvoid/fusion/d0")
            ->fake("copy")
            ->expect(from: "/#/c/f2", to: "/tmp/other/valvoid/fusion/c/f2")
            ->expect(from: "/#/d0/f3", to: "/tmp/other/valvoid/fusion/d0/f3")
            ->expect(from: "/#/f0", to: "/tmp/other/valvoid/fusion/f0")
            ->expect(from: "/#/fusion", to: "/tmp/other/valvoid/fusion/fusion")
            ->expect(from: "/tmp/state/f1", to: "/#/f1")
            ->fake("delete")
            ->expect(file: "/#/c/f2")
            ->expect(file: "/#/d0/f3")
            ->expect(file: "/#/d0")
            ->expect(file: "/#/f0")
            ->expect(file: "/tmp/state/f1");

        $group->fake("hasDownloadable")
            ->return(true)
            ->fake("getExternalRootMetadata")
            ->return($external)
            ->fake("getInternalRootMetadata")
            ->return($internal)
            ->fake("getExternalMetas")
            ->return(["valvoid/fusion" => $external])
            ->fake("getInternalMetas")
            ->return(["valvoid/fusion" => $internal]);

        $external->fake("getCategory")
            ->return(ExternalCategory::DOWNLOADABLE)
            ->repeat(1)
            ->fake("getStatefulPath")
            ->return("/c")
            ->fake("onInstall")
            ->return(true)
            ->fake("getContent")
            ->return(["###"])
            ->fake("getDir")
            ->return("");

        $internal->fake("getCategory")
            ->return(InternalCategory::OBSOLETE)
            ->repeat(1)
            ->fake("getStatefulPath")
            ->return("/c")
            ->fake("onDelete")
            ->return(true);

        $box->fake("get")
            ->expect(class: Root::class,
                arguments: ["dir" => "/tmp/other/valvoid/fusion"])
            ->return($root)
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->return($content);

        $bus->fake("broadcast")
            ->expect(event: $root);

        $dir->fake("getDirname")
            ->return("/#/d0/d1")
            ->fake("getFilenames")
            ->expect(dir: "/#")
            ->return(["c", "d0", "f0", "fusion"])
            ->expect(dir: "/#/c")
            ->return(["f2"])
            ->expect(dir: "/#/d0")
            ->return(["f3"])
            ->expect(dir: "/#")
            ->return(["c", "d0", "f0", "fusion"])
            ->expect(dir: "/#/c")
            ->return(["f2"])
            ->expect(dir: "/#/d0")
            ->return(["f3"])
            ->expect(dir: "/tmp/state")
            ->return(["c", "f1", "fusion"])
            ->expect(dir: "/tmp/state/c")
            ->return([])
            ->fake("is")
            ->expect(dir: "/#/c")
            ->return(true)
            ->expect(dir: "/#/c/f2")
            ->return(false)
            ->expect(dir: "/#/d0")
            ->return(true)
            ->expect(dir: "/#/d0/f3")
            ->return(false)
            ->expect(dir: "/#/f0")
            ->expect(dir: "/#/fusion")
            ->expect(dir: "/#/c")
            ->return(true)
            ->expect(dir: "/tmp/state/c")
            ->expect(dir: "/tmp/state/f1")
            ->return(false)
            ->expect(dir: "/tmp/state/fusion");

        $file->fake("is")
            ->expect(file: "/#/c")
            ->return(false)
            ->expect(file: "/#/c/f2")
            ->return(true)
            ->expect(file: "/#/d0")
            ->return(false)
            ->expect(file: "/#/d0/f3")
            ->return(true)
            ->expect(file: "/#/f0")
            ->expect(file: "/#/fusion")
            ->fake("get")
            ->expect(file: "/tmp/state/fusion")
            ->return("###")
            ->fake("put")
            ->expect(file: "/#/fusion", data: "")
            ->return(1)
            ->expect(file: "/#/fusion", data: "###")
            ->fake("exists")
            ->expect(file: "/#/c")
            ->return(true);

        $system->fake("getOsFamily")
            ->return("Windows")
            ->fake("getBacktrace")
            ->return([["file" => "/#/fusion"]]);

        $task->execute();
    }

    public function testShiftNestedWithExecutedFiles(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $group = $this->createMock(Group::class);
        $log = $this->createMock(Log::class);
        $directory = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $dir = $this->createMock(DirWrapper::class);
        $system = $this->createMock(System::class);
        $internal = $this->createMock(Internal::class);
        $external = $this->createMock(External::class);
        $content = $this->createStub(Content::class);
        $root = $this->createStub(Root::class);
        $task = new Shift(
            box: $box,
            bus: $bus,
            group: $group,
            log: $log,
            directory: $directory,
            file: $file,
            dir: $dir,
            system: $system,
            config: []
        );

        $log->fake("info")
            ->return(null)
            ->repeat(2);

        $system->fake("getOsFamily")
            ->return("Windows")
            ->fake("getBacktrace")
            ->return([["file" => "/#/deps/valvoid/fusion/fusion"]]);

        $directory->fake("getRootDir")
            ->return("/#")
            ->fake("getStateDir")
            ->return("/tmp/state")
            ->repeat(1)
            ->fake("getPackagesDir")
            ->return("/tmp/packages")
            ->fake("getTaskDir")
            ->return("/tmp/task")
            ->fake("getOtherDir")
            ->return("/tmp/other")
            ->repeat(1)
            ->fake("createDir")
            ->expect(dir: "/tmp/other/valvoid/fusion")
            ->expect(dir: "/tmp/other/valvoid/fusion/c")
            ->expect(dir: "/tmp/other/valvoid/fusion/deps")
            ->expect(dir: "/tmp/other/valvoid/fusion/deps/valvoid")
            ->expect(dir: "/tmp/other/valvoid/fusion/deps/valvoid/fusion")
            ->expect(dir: "/tmp/other/valvoid/fusion/deps/valvoid/fusion/d0")
            ->expect(dir: "/#/deps/valvoid/fusion/d1")
            ->fake("delete")
            ->expect(file: "/#/c/f2")
            ->expect(file: "/#/deps/valvoid/fusion/d0/f4")
            ->expect(file: "/#/deps/valvoid/fusion/d0")
            ->expect(file: "/#/deps/valvoid/fusion/f3")
            ->expect(file: "/tmp/state/deps/valvoid/fusion/d1/f6")
            ->expect(file: "/tmp/state/deps/valvoid/fusion/f5")
            ->fake("copy")
            ->expect(from: "/tmp/state/c/f1", to: "/#/c/f1")
            ->expect(from: "/#/c/f2", to: "/tmp/other/valvoid/fusion/c/f2")
            ->expect(from: "/#/deps/valvoid/fusion/d0/f4",
                to: "/tmp/other/valvoid/fusion/deps/valvoid/fusion/d0/f4")
            ->expect(from: "/#/deps/valvoid/fusion/f3",
                to: "/tmp/other/valvoid/fusion/deps/valvoid/fusion/f3")
            ->expect(from: "/#/deps/valvoid/fusion/fusion",
                to: "/tmp/other/valvoid/fusion/deps/valvoid/fusion/fusion")
            ->expect(from: "/tmp/state/deps/valvoid/fusion/d1/f6",
                to: "/#/deps/valvoid/fusion/d1/f6")
            ->expect(from: "/tmp/state/deps/valvoid/fusion/f5",
                to: "/#/deps/valvoid/fusion/f5");

        $group->fake("hasDownloadable")
            ->return(true)
            ->fake("getExternalRootMetadata")
            ->return(null)
            ->fake("getExternalMetas")
            ->return(["valvoid/fusion" => $external])
            ->fake("getInternalMetas")
            ->return([
                "i0" => $internal,
                "valvoid/fusion" => $internal
            ]);

        $box->fake("get")
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->return($content)
            ->expect(class: Root::class,
                arguments: ["dir" => "/tmp/other/valvoid/fusion"])
            ->return($root)
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->return($content);

        $bus->fake("broadcast")
            ->expect(event: $root);

        $internal->fake("getCategory")
            ->return(InternalCategory::RECYCLABLE) // i0
            ->return(InternalCategory::OBSOLETE) // fusion
            ->fake("getDir")
            ->return("")
            ->fake("getStatefulPath")
            ->return("/c")
            ->fake("getSource")
            ->return("/#")
            ->return("/#/deps/valvoid/fusion")
            ->fake("getStructureMutables")
            ->return([])
            ->fake("onUpdate")
            ->return(true)
            ->fake("onDelete")
            ->return(true)
            ->fake("getContent")
            ->return(["###"]);

        $external->fake("getCategory")
            ->return(ExternalCategory::DOWNLOADABLE)
            ->fake("onInstall")
            ->return(true)
            ->fake("getContent")
            ->return(["###"])
            ->fake("getDir")
            ->return("/deps/valvoid/fusion")
            ->repeat(1);

        $dir->fake("getDirname")
            ->return("/#/d0/d1")
            ->fake("getFilenames")
            ->expect(dir: "/#/c")
            ->return(["f2"])
            ->expect(dir: "/tmp/state/c")
            ->return(["f1"])
            ->expect(dir: "/#")
            ->return(["c", "deps"])
            ->expect(dir: "/#/c")
            ->return(["f2"])
            ->expect(dir: "/#/deps")
            ->return(["valvoid"])
            ->expect(dir: "/#/deps/valvoid")
            ->return(["fusion"])
            ->expect(dir: "/#/deps/valvoid/fusion")
            ->return(["d0", "f3", "fusion"])
            ->expect(dir: "/#/deps/valvoid/fusion/d0")
            ->return(["f4"])
            ->expect(dir: "/#/deps/valvoid/fusion")
            ->return(["d0", "f3", "fusion"])
            ->expect(dir: "/#/deps/valvoid/fusion/d0")
            ->return(["f4"])
            ->expect(dir: "/tmp/state/deps/valvoid/fusion")
            ->return(["d1", "f5", "fusion"])
            ->expect(dir: "/tmp/state/deps/valvoid/fusion/d1")
            ->return(["f6"])
            ->fake("is")
            ->expect(dir: "/#/c/f2")
            ->return(false)
            ->expect(dir: "/#/deps/valvoid/fusion/d0")
            ->return(true)
            ->expect(dir: "/#/deps/valvoid/fusion/d0/f4")
            ->return(false)
            ->expect(dir: "/#/deps/valvoid/fusion/f3")
            ->return(false)
            ->expect(dir: "/#/deps/valvoid/fusion/fusion")
            ->expect(dir: "/tmp/state/deps/valvoid/fusion/d1")
            ->return(true)
            ->expect(dir: "/tmp/state/deps/valvoid/fusion/d1/f6")
            ->return(false)
            ->expect(dir: "/tmp/state/deps/valvoid/fusion/f5")
            ->expect(dir: "/tmp/state/deps/valvoid/fusion/fusion");

        $file->fake("exists")
            ->expect(file: "/#/c")
            ->return(true)
            ->expect(file: "/#/deps/valvoid/fusion")
            ->expect(file: "/#/deps/valvoid/fusion/d1")
            ->return(false)
            ->fake("is")
            ->expect(file: "/tmp/state/c/f1")
            ->return(true)
            ->expect(file: "/#/c")
            ->return(false)
            ->expect(file: "/#/c/f2")
            ->return(true)
            ->expect(file: "/#/deps")
            ->return(false)
            ->expect(file: "/#/deps/valvoid")
            ->expect(file: "/#/deps/valvoid/fusion")
            ->expect(file: "/#/deps/valvoid/fusion/d0")
            ->expect(file: "/#/deps/valvoid/fusion/d0/f4")
            ->return(true)
            ->expect(file: "/#/deps/valvoid/fusion/f3")
            ->expect(file: "/#/deps/valvoid/fusion/fusion")
            ->fake("put")
            ->expect(file: "/#/deps/valvoid/fusion/fusion", data: "")
            ->return(1)
            ->expect(file: "/#/deps/valvoid/fusion/fusion", data: "###")
            ->fake("get")
            ->expect(file: "/tmp/state/deps/valvoid/fusion/fusion")
            ->return("###");

        $task->execute();
    }
}