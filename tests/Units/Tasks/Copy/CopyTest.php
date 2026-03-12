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

namespace Valvoid\Fusion\Tests\Units\Tasks\Copy;

use Valvoid\Box\Box;
use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Metadata\External\External;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Tasks\Copy\Copy;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Metadata\Internal\Category;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Fusion\Wrappers\Dir as DirWrapper;
use Valvoid\Reflex\Test\Wrapper;

class CopyTest extends Wrapper
{
    public function testPackageCategory(): void
    {
        $box = $this->createMock(Box::class);
        $directory = $this->createMock(Dir::class);
        $dir = $this->createMock(DirWrapper::class);
        $file = $this->createMock(File::class);
        $group = $this->createMock(Group::class);
        $log = $this->createMock(Log::class);
        $internal = $this->createMock(Internal::class);
        $content = $this->createStub(Content::class);
        $task = new Copy(
            box: $box,
            group: $group,
            log: $log,
            directory: $directory,
            file: $file,
            dir: $dir,
            config: []);

        $log->fake("info")
            ->return(null)
            ->repeat(2);

        $group->fake("hasDownloadable")
            ->return(true)
            ->fake("getExternalMetas")
            ->return([])
            ->fake("getInternalMetas")
            ->return([
                "i0" => $internal,
                "i1" => $internal,
                "i2" => $internal
            ]);

        $directory->fake("getPackagesDir")
            ->return("##")
            ->fake("createDir")
            ->expect(dir: "##/i0")
            ->expect(dir: "##/i0/d0")
            ->expect(dir: "##/i1")
            ->fake("copy")
            ->expect(from: "/s0/d0/f1", to: "##/i0/d0/f1")
            ->expect(from: "/s0/f0", to: "##/i0/f0")
            ->expect(from: "/s0/deps/i1/f2", to: "##/i1/f2");

        $internal->fake("getCategory")
            ->return(Category::RECYCLABLE)
            ->return(Category::MOVABLE)
            ->return(Category::OBSOLETE)
            ->fake("getSource")
            ->return("/s0")
            ->return("/s0/deps/i1")
            ->fake("getStatefulPath")
            ->return("/state")
            ->repeat(1)
            ->fake("getStructureSources")
            ->return(["/deps" => ""])
            ->return([])
            ->fake("getContent")
            ->return(["###"])
            ->repeat(1);

        $box->fake("get")
            ->expect(class: Content::class)
            ->return($content)
            ->repeat(1);

        $dir->fake("getFilenames")
            ->expect(dir: "/s0")
            ->return(["d0", "f0"])
            ->expect(dir: "/s0/d0")
            ->return(["f1"])
            ->expect(dir: "/s0/deps/i1")
            ->return(["f2"]);

        $file->fake("is")
            ->expect(file: "/s0/d0")
            ->return(false)
            ->expect(file: "/s0/d0/f1")
            ->return(true)
            ->expect(file: "/s0/f0")
            ->expect(file: "/s0/deps/i1/f2");

        $task->execute();
    }

    public function testCustomMigration(): void
    {
        $box = $this->createMock(Box::class);
        $directory = $this->createMock(Dir::class);
        $dir = $this->createMock(DirWrapper::class);
        $file = $this->createMock(File::class);
        $group = $this->createMock(Group::class);
        $log = $this->createMock(Log::class);
        $internal = $this->createMock(Internal::class);
        $external = $this->createMock(External::class);
        $task = new Copy(
            box: $box,
            group: $group,
            log: $log,
            directory: $directory,
            file: $file,
            dir: $dir,
            config: []);

        $log->fake("info")
            ->return(null);

        $group->fake("hasDownloadable")
            ->return(true)
            ->fake("getExternalMetas")
            ->return([
                "i0" => $external,
                "i1" => $external
            ])
            ->fake("getInternalMetas")
            ->return([
                "i0" => $internal,
                "i1" => $internal
            ]);

        $directory->fake("getPackagesDir")
            ->return("##");

        $internal->fake("getCategory")
            ->return(Category::OBSOLETE)
            ->repeat(1)
            ->fake("getVersion")
            ->return("1.0.0")
            ->return("2.0.0")
            ->fake("onMigrate")
            ->return(true);

        $external->fake("getVersion")
            ->return("2.0.0")
            ->return("1.0.0")
            ->fake("onMigrate")
            ->return(true);

        $task->execute();
    }
}