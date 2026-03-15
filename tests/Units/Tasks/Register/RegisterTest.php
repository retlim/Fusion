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

namespace Valvoid\Fusion\Tests\Units\Tasks\Register;

use Valvoid\Box\Box;
use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Metadata\External\External;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tasks\Register\Register;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Reflex\Test\Wrapper;

class RegisterTest extends Wrapper
{
    public function testRefreshAutoloader(): void
    {
        $box = $this->createMock(Box::class);
        $group = $this->createMock(Group::class);
        $directory = $this->createMock(Dir::class);
        $log = $this->createMock(Log::class);
        $file = $this->createMock(File::class);
        $internal = $this->createMock(Internal::class);
        $content = $this->createMock(Content::class);
        $task = new Register(
            box: $box,
            group: $group,
            directory: $directory,
            log: $log,
            file: $file,
            config: []
        );

        $group->fake("hasDownloadable")
            ->return(false)
            ->fake("getInternalRootMetadata")
            ->return($internal)
            ->fake("getInternalMetas")
            ->return([
                "i0" => $internal,
                "i1" => $internal,
                "i2" => $internal,
                "i3" => $internal
            ]);

        $internal->fake("getCategory")
            ->return(InternalCategory::RECYCLABLE)
            ->repeat(2)
            ->return(InternalCategory::OBSOLETE)
            ->fake("getContent")
            ->return(["###"])
            ->repeat(2)
            ->fake("getSource")
            ->return("/s0")
            ->return("/s1")
            ->return("/s2")
            ->fake("getStatefulPath")
            ->return("/c0")
            ->return("/c1")
            ->return("/c2")
            ->return("/c0") // root call
            ->fake("getDir")
            ->return("")
            ->return("/deps/i1")
            ->return("/deps/i2");

        $log->fake("info")
            ->return(null)
            ->repeat(3);

        $box->fake("get")
            ->return($content)
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->repeat(2);

        $directory->fake("getStatefulDir")
            ->return("#")
            ->fake("createDir")
            ->expect(dir: "#");

        $prefixes = "<?php\nreturn [" .
            "\n\t'I2' => '/deps/i2/d2/f4'," .
            "\n\t'I1' => '/deps/i1/d1/f2'," .
            "\n\t'I0' => '/d0/f0',\n];";

        $file->fake("exists")
            ->expect(file: "/s0/c0/lazy.php")
            ->return(true)
            ->expect(file: "/s0/c0/asap.php")
            ->expect(file: "/s1/c1/lazy.php")
            ->expect(file: "/s1/c1/asap.php")
            ->expect(file: "/s2/c2/lazy.php")
            ->expect(file: "/s2/c2/asap.php")
            ->fake("require")
            ->expect(file: "/s0/c0/lazy.php")
            ->return(["I0" => "/d0/f0.php"])
            ->expect(file: "/s0/c0/asap.php")
            ->return(["/f1.php"])
            ->expect(file: "/s1/c1/lazy.php")
            ->return(["I1" => "/d1/f2.php"])
            ->expect(file: "/s1/c1/asap.php")
            ->return(["/f3.php"])
            ->expect(file: "/s2/c2/lazy.php")
            ->return(["I2" => "/d2/f4.php"])
            ->expect(file: "/s2/c2/asap.php")
            ->return(["/f5.php"])
            ->fake("get")
            ->expect(file: dirname(__DIR__, 4) .
                "/src/Tasks/Register/Autoloader.php")
            ->return("\$asap = [];\$prefixes = []")
            ->fake("put")
            ->return(1)
            ->expect(file: "#/Autoloader.php",
                data: "\$asap = [" .
                "\n\t\t'/f1.php'," .
                "\n\t\t'/deps/i1/f3.php'," .
                "\n\t\t'/deps/i2/f5.php'," .
                "\n\t];\$prefixes = [" .
                "\n\t\t'I2' => '/deps/i2/d2/f4'," .
                "\n\t\t'I1' => '/deps/i1/d1/f2'," .
                "\n\t\t'I0' => '/d0/f0'," .
                "\n\t]")
            ->expect(file: "/s0/c0/prefixes.php", data: $prefixes)
            ->expect(file: "/s1/c1/prefixes.php", data: $prefixes)
            ->expect(file: "/s2/c2/prefixes.php", data: $prefixes);

        $task->execute();
    }

    public function testNewStateAutoloader(): void
    {
        $box = $this->createMock(Box::class);
        $group = $this->createMock(Group::class);
        $directory = $this->createMock(Dir::class);
        $log = $this->createMock(Log::class);
        $file = $this->createMock(File::class);
        $internal = $this->createMock(Internal::class);
        $external = $this->createMock(External::class);
        $content = $this->createMock(Content::class);
        $task = new Register(
            box: $box,
            group: $group,
            directory: $directory,
            log: $log,
            file: $file,
            config: []
        );

        $group->fake("hasDownloadable")
            ->return(true)
            ->fake("getExternalRootMetadata")
            ->return($external)
            ->fake("getInternalMetas")
            ->return(["i0" => $internal])
            ->fake("getExternalMetas")
            ->return([
                "i0" => $external,
                "i1" => $external,
                "i2" => $external
            ]);

        $internal->fake("getCategory")
            ->return(InternalCategory::OBSOLETE);

        $external->fake("getId")
            ->return("i0")
            ->fake("getCategory")
            ->return(ExternalCategory::DOWNLOADABLE)
            ->repeat(2)
            ->fake("getContent")
            ->return(["###"])
            ->repeat(2)
            ->fake("getStatefulPath")
            ->return("/c0")
            ->return("/c1")
            ->return("/c2")
            ->return("/c0") // root call
            ->fake("getDir")
            ->return("")
            ->return("/deps/i1")
            ->return("/deps/i2");

        $log->fake("info")
            ->return(null)
            ->repeat(3);

        $box->fake("get")
            ->return($content)
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->repeat(2);

        $directory->fake("getPackagesDir")
            ->return("#p")
            ->fake("createDir")
            ->expect(dir: "#p/i0/c0");

        $prefixes = "<?php\nreturn [" .
            "\n\t'I2' => '/deps/i2/d2/f4'," .
            "\n\t'I1' => '/deps/i1/d1/f2'," .
            "\n\t'I0' => '/d0/f0',\n];";

        $file->fake("exists")
            ->expect(file: "#p/i0/c0/lazy.php")
            ->return(true)
            ->expect(file: "#p/i0/c0/asap.php")
            ->expect(file: "#p/i1/c1/lazy.php")
            ->expect(file: "#p/i1/c1/asap.php")
            ->expect(file: "#p/i2/c2/lazy.php")
            ->expect(file: "#p/i2/c2/asap.php")
            ->fake("require")
            ->expect(file: "#p/i0/c0/lazy.php")
            ->return(["I0" => "/d0/f0.php"])
            ->expect(file: "#p/i0/c0/asap.php")
            ->return(["/f1.php"])
            ->expect(file: "#p/i1/c1/lazy.php")
            ->return(["I1" => "/d1/f2.php"])
            ->expect(file: "#p/i1/c1/asap.php")
            ->return(["/f3.php"])
            ->expect(file: "#p/i2/c2/lazy.php")
            ->return(["I2" => "/d2/f4.php"])
            ->expect(file: "#p/i2/c2/asap.php")
            ->return(["/f5.php"])
            ->fake("get")
            ->expect(file: dirname(__DIR__, 4) .
                "/src/Tasks/Register/Autoloader.php")
            ->return("\$asap = [];\$prefixes = []")
            ->fake("put")
            ->return(1)
            ->expect(file: "#p/i0/c0/Autoloader.php",
                data: "\$asap = [" .
                "\n\t\t'/f1.php'," .
                "\n\t\t'/deps/i1/f3.php'," .
                "\n\t\t'/deps/i2/f5.php'," .
                "\n\t];\$prefixes = [" .
                "\n\t\t'I2' => '/deps/i2/d2/f4'," .
                "\n\t\t'I1' => '/deps/i1/d1/f2'," .
                "\n\t\t'I0' => '/d0/f0'," .
                "\n\t]")
            ->expect(file: "#p/i0/c0/prefixes.php", data: $prefixes)
            ->expect(file: "#p/i1/c1/prefixes.php", data: $prefixes)
            ->expect(file: "#p/i2/c2/prefixes.php", data: $prefixes);

        $task->execute();
    }
}