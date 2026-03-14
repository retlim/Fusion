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

namespace Valvoid\Fusion\Tests\Units\Tasks\Inflate;

use Valvoid\Box\Box;
use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Metadata\External\External;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tasks\Inflate\Inflate;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Fusion\Wrappers\Dir as DirWrapper;
use Valvoid\Reflex\Test\Wrapper;

class InflateTest extends Wrapper
{
    public function testRefresh(): void
    {
        $box = $this->createMock(Box::class);
        $log = $this->createMock(Log::class);
        $directory = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $dir = $this->createMock(DirWrapper::class);
        $group = $this->createMock(Group::class);
        $internal = $this->createMock(Internal::class);
        $content = $this->createMock(Content::class);
        $task = new Inflate(
            box: $box,
            group: $group,
            directory: $directory,
            log: $log,
            file: $file,
            dir: $dir,
            config: []
        );

        $group->fake("hasDownloadable")
            ->return(false)
            ->fake("getInternalMetas")
            ->return([
                "i0" => $internal,
                "i1" => $internal,
                "i2" => $internal,
                "i3" => $internal
            ]);

        $log->fake("info")
            ->return(null)
            ->repeat(3);

        $internal->fake("getCategory")
            ->return(InternalCategory::RECYCLABLE)
            ->repeat(2)
            ->return(InternalCategory::OBSOLETE) // ignore
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
            ->return("/c2");

        $box->fake("get")
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->return($content)
            ->repeat(2);

        $dir->fake("getFilenames")
            ->expect(dir: "/s0")
            ->return(["d0", "f0.php"])
            ->expect(dir: "/s0/d0")
            ->return(["f1.php", "f2"])
            ->expect(dir: "/s1")
            ->return(["f3.php"])
            ->expect(dir: "/s2")
            ->return(["f4.php", "f5.php"])
            ->fake("is")
            ->expect(dir: "/s0/d0")
            ->return(true)
            ->expect(dir: "/s0/d0/f1.php")
            ->return(false)
            ->expect(dir: "/s0/d0/f2")
            ->expect(dir: "/s0/f0.php")
            ->expect(dir: "/s1/f3.php")
            ->expect(dir: "/s2/f4.php")
            ->expect(dir: "/s2/f5.php");

        $file->fake("get")
            ->expect(file: "/s0/d0/f1.php")
            ->return("<?php\nnamespace I0 {\nfunction whatever() {}\n}")
            ->expect(file: "/s0/f0.php")
            ->return("<?php\nnamespace I0;\nclass Any {}")
            ->expect(file: "/s1/f3.php")
            ->return("<?php\nnamespace I1\Any;\ninterface Any {}")
            ->expect(file: "/s2/f4.php")
            ->return("<?php\nnamespace I2;\ntrait Any {}")
            ->expect(file: "/s2/f5.php")
            ->return("<?php\nnamespace I;\nclass Any {}")
            ->fake("is")
            ->expect(file: "/s0/c0/lazy.php")
            ->return(true)
            ->expect(file: "/s0/c0/asap.php")
            ->expect(file: "/s1/c1/lazy.php")
            ->expect(file: "/s1/c1/asap.php")
            ->expect(file: "/s2/c2/lazy.php")
            ->expect(file: "/s2/c2/asap.php")
            ->fake("unlink")
            ->expect(file: "/s0/c0/lazy.php")
            ->return(true)
            ->expect(file: "/s0/c0/asap.php")
            ->expect(file: "/s1/c1/lazy.php")
            ->expect(file: "/s1/c1/asap.php")
            ->expect(file: "/s2/c2/lazy.php")
            ->expect(file: "/s2/c2/asap.php")
            ->fake("put")
            ->return(1)
            ->expect(file: "/s0/c0/lazy.php",
                data: "<?php\nreturn [\n\t'I0\Any' => '/f0.php',\n];")
            ->expect(file: "/s0/c0/asap.php",
                data: "<?php\nreturn [\n\t'/d0/f1.php',\n];")
            ->expect(file: "/s1/c1/lazy.php",
                data: "<?php\nreturn [\n\t'I1\Any\Any' => '/f3.php',\n];")
            ->expect(file: "/s2/c2/lazy.php",
                data: "<?php\nreturn [\n\t'I2\Any' => '/f4.php',\n];");

        $task->execute();
    }

    public function testDownloadable(): void
    {
        $box = $this->createMock(Box::class);
        $log = $this->createMock(Log::class);
        $directory = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $dir = $this->createMock(DirWrapper::class);
        $group = $this->createMock(Group::class);
        $internal = $this->createMock(Internal::class);
        $external = $this->createMock(External::class);
        $content = $this->createMock(Content::class);
        $task = new Inflate(
            box: $box,
            group: $group,
            directory: $directory,
            log: $log,
            file: $file,
            dir: $dir,
            config: []
        );

        $group->fake("hasDownloadable")
            ->return(true)
            ->fake("getInternalMetas")
            ->return(["i0" => $internal])
            ->fake("getExternalMetas")
            ->return([
                "i0" => $external,
                "i1" => $external,
                "i2" => $external
            ]);

        $directory->fake("getPackagesDir")
            ->return("/#");

        $log->fake("info")
            ->return(null)
            ->repeat(3);

        $internal->fake("getCategory")
            ->return(InternalCategory::OBSOLETE);

        $external->fake("getCategory")
            ->return(ExternalCategory::DOWNLOADABLE)
            ->repeat(2)
            ->fake("getContent")
            ->return(["###"])
            ->repeat(2)
            ->fake("getStatefulPath")
            ->return("/state")
            ->repeat(2);

        $box->fake("get")
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->return($content)
            ->repeat(2);

        $dir->fake("getFilenames")
            ->expect(dir: "/#/i0")
            ->return(["d0", "f0.php"])
            ->expect(dir: "/#/i0/d0")
            ->return(["f1"])
            ->expect(dir: "/#/i1")
            ->return(["f2.php"])
            ->expect(dir: "/#/i2")
            ->return(["f3.php"])
            ->fake("is")
            ->expect(dir: "/#/i0/d0")
            ->return(true)
            ->expect(dir: "/#/i0/d0/f1")
            ->return(false)
            ->expect(dir: "/#/i0/f0.php")
            ->expect(dir: "/#/i1/f2.php")
            ->expect(dir: "/#/i2/f3.php");

        $file->fake("get")
            ->expect(file: "/#/i0/f0.php")
            ->return("<?php\nnamespace I0;\nfinal class Any {}")
            ->expect(file: "/#/i1/f2.php")
            ->return("<?php\nnamespace I1;\nabstract class Any {}")
            ->expect(file: "/#/i2/f3.php")
            ->return("<?php\nnamespace I2;\nenum Any {}")
            ->fake("is")
            ->expect(file: "/#/i0/state/lazy.php")
            ->return(true)
            ->expect(file: "/#/i0/state/asap.php")
            ->expect(file: "/#/i1/state/lazy.php")
            ->expect(file: "/#/i1/state/asap.php")
            ->expect(file: "/#/i2/state/lazy.php")
            ->expect(file: "/#/i2/state/asap.php")
            ->fake("unlink")
            ->expect(file: "/#/i0/state/lazy.php")
            ->return(true)
            ->expect(file: "/#/i0/state/asap.php")
            ->expect(file: "/#/i1/state/lazy.php")
            ->expect(file: "/#/i1/state/asap.php")
            ->expect(file: "/#/i2/state/lazy.php")
            ->expect(file: "/#/i2/state/asap.php")
            ->fake("put")
            ->return(1)
            ->expect(file: "/#/i0/state/lazy.php",
                data: "<?php\nreturn [\n\t'I0\Any' => '/f0.php',\n];")
            ->expect(file: "/#/i1/state/lazy.php",
                data: "<?php\nreturn [\n\t'I1\Any' => '/f2.php',\n];")
            ->expect(file: "/#/i2/state/lazy.php",
                data: "<?php\nreturn [\n\t'I2\Any' => '/f3.php',\n];");

        $task->execute();
    }
}