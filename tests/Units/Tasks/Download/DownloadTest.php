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

namespace Valvoid\Fusion\Tests\Units\Tasks\Download;

use PharData;
use Valvoid\Box\Box;
use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Hub\Hub;
use Valvoid\Fusion\Hub\Responses\Cache\Archive;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Metadata\External\External;
use Valvoid\Fusion\Tasks\Download\Download;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Wrappers\Dir as DirWrapper;
use Valvoid\Fusion\Wrappers\Extension;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Reflex\Test\Wrapper;
use ZipArchive;

class DownloadTest extends Wrapper
{
    public function testZipArchive(): void
    {
        $box = $this->createMock(Box::class);
        $log = $this->createStub(Log::class);
        $hub = $this->createMock(Hub::class);
        $directory = $this->createMock(Dir::class);
        $dir = $this->createMock(DirWrapper::class);
        $file = $this->createMock(File::class);
        $group = $this->createMock(Group::class);
        $external = $this->createMock(External::class);
        $extension = $this->createMock(Extension::class);
        $zip = $this->createMock(ZipArchive::class);
        $content = $this->createMock(Content::class);
        $task = new Download(
            box: $box,
            group: $group,
            log: $log,
            hub: $hub,
            directory: $directory,
            extension: $extension,
            file: $file,
            dir: $dir,

            // task id for directory
            config: ["id" => "test"]);

        $log->fake("info")
            ->return(null)
            ->repeat(2);

        $group->fake("hasDownloadable")
            ->return(true)
            ->fake("getExternalMetas")
            ->return([
                "i0" => $external,
                "i1" => $external
            ]);

        $external->fake("getCategory")
            ->return(ExternalCategory::DOWNLOADABLE)
            ->repeat(1)
            ->fake("getSource")
            ->return(["#0"])
            ->return(["#1"])
            ->fake("getId")
            ->return("i0")
            ->return("i1")
            ->fake("getLayers")
            ->return(["object" => ["version" => "3.4.5"]]) // bot meta
            ->return([])
            ->fake("getContent")
            ->return(["#i0c"])
            ->return(["#i1c"]);

        $directory->fake("getPackagesDir")
            ->return("/p")
            ->fake("getTaskDir")
            ->return("/t")
            ->fake("createDir")
            ->expect(dir: "/p/i0")
            ->expect(dir: "/p/i1")
            ->fake("rename")
            ->expect(from:  "/t/test/i0/#archiveroot", to: "/p/i0")
            ->expect(from:  "/t/test/i1", to: "/p/i1");

        $extension->fake("isLoaded")
            ->expect(extension: "zip")
            ->return(true);

        $hub->fake("addArchiveRequest")
            ->expect(source: ["#0"])
            ->return(0)
            ->expect(source: ["#1"])
            ->return(1)
            ->fake("executeRequests")
            ->hook(function ($callback) {
                $callback(new Archive(0, "/d/0"));
                $callback(new Archive(1, "/d/1"));
            });

        $box->fake("get")
            ->expect(class: ZipArchive::class)
            ->return($zip)
            ->expect(class: Content::class, arguments: ["content" => ["#i0c"]])
            ->return($content)
            ->expect(class: ZipArchive::class)
            ->return($zip)
            ->expect(class: Content::class, arguments: ["content" => ["#i1c"]])
            ->return($content);

        $zip->fake("open")
            ->expect(filename: "/d/0/archive.zip")
            ->return(true)
            ->expect(filename: "/d/1/archive.zip")
            ->fake("extractTo")
            ->expect(pathto: "/t/test/i0")
            ->return(true)
            ->expect(pathto: "/t/test/i1")
            ->fake("close")
            ->return(true)
            ->repeat(1);

        $file->fake("exists")
            ->expect(file:  "/t/test/i0/fusion.json")
            ->return(false)
            ->expect(file:  "/t/test/i0/#archiveroot/fusion.json")
            ->return(true)
            ->expect(file:  "/t/test/i1/fusion.json")
            ->fake("put")
            ->return(1)
            ->expect(file:  "/p/i0/fusion.bot.php",
                data: "<?php\nreturn [\n\t\"version\" => \"3.4.5\"\n];");

        $dir->fake("getFilenames")
            ->expect(dir: "/t/test/i0")
            ->return(["#archiveroot"]);

        $task->execute();
    }

    public function testPharData(): void
    {
        $box = $this->createMock(Box::class);
        $log = $this->createStub(Log::class);
        $hub = $this->createMock(Hub::class);
        $directory = $this->createMock(Dir::class);
        $dir = $this->createMock(DirWrapper::class);
        $file = $this->createMock(File::class);
        $group = $this->createMock(Group::class);
        $external = $this->createMock(External::class);
        $extension = $this->createMock(Extension::class);
        $phar = $this->createStub(PharData::class);
        $content = $this->createMock(Content::class);
        $task = new Download(
            box: $box,
            group: $group,
            log: $log,
            hub: $hub,
            directory: $directory,
            extension: $extension,
            file: $file,
            dir: $dir,

            // task id for directory
            config: ["id" => "test"]);

        $log->fake("info")
            ->return(null)
            ->repeat(2);

        $group->fake("hasDownloadable")
            ->return(true)
            ->fake("getExternalMetas")
            ->return([
                "i0" => $external,
                "i1" => $external
            ]);

        $external->fake("getCategory")
            ->return(ExternalCategory::DOWNLOADABLE)
            ->repeat(1)
            ->fake("getSource")
            ->return(["#0"])
            ->return(["#1"])
            ->fake("getId")
            ->return("i0")
            ->return("i1")
            ->fake("getLayers")
            ->return(["object" => ["version" => "3.4.5"]]) // bot meta
            ->return([])
            ->fake("getContent")
            ->return(["#i0c"])
            ->return(["#i1c"]);

        $directory->fake("getPackagesDir")
            ->return("/p")
            ->fake("getTaskDir")
            ->return("/t")
            ->fake("createDir")
            ->expect(dir: "/p/i0")
            ->expect(dir: "/p/i1")
            ->fake("rename")
            ->expect(from:  "/t/test/i0/#archiveroot", to: "/p/i0")
            ->expect(from:  "/t/test/i1", to: "/p/i1");

        $extension->fake("isLoaded")
            ->expect(extension: "zip")
            ->return(false);

        $hub->fake("addArchiveRequest")
            ->expect(source: ["#0"])
            ->return(0)
            ->expect(source: ["#1"])
            ->return(1)
            ->fake("executeRequests")
            ->hook(function ($callback) {
                $callback(new Archive(0, "/d/0"));
                $callback(new Archive(1, "/d/1"));
            });

        $box->fake("get")
            ->expect(class: PharData::class, arguments: ["filename" => "/d/0/archive.zip"])
            ->return($phar)
            ->expect(class: Content::class, arguments: ["content" => ["#i0c"]])
            ->return($content)
            ->expect(class: PharData::class, arguments: ["filename" => "/d/1/archive.zip"])
            ->return($phar)
            ->expect(class: Content::class, arguments: ["content" => ["#i1c"]])
            ->return($content);

        $phar->fake("extractTo")
            ->return(true)
            ->repeat(1)
            ->fake("__destruct")
            ->return(null);

        $file->fake("exists")
            ->expect(file:  "/t/test/i0/fusion.json")
            ->return(false)
            ->expect(file:  "/t/test/i0/#archiveroot/fusion.json")
            ->return(true)
            ->expect(file:  "/t/test/i1/fusion.json")
            ->fake("put")
            ->return(1)
            ->expect(file:  "/p/i0/fusion.bot.php",
                data: "<?php\nreturn [\n\t\"version\" => \"3.4.5\"\n];");

        $dir->fake("getFilenames")
            ->expect(dir: "/t/test/i0")
            ->return(["#archiveroot"]);

        $task->execute();
    }
}