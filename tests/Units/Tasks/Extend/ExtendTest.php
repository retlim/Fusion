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

namespace Valvoid\Fusion\Tests\Units\Tasks\Extend;

use Valvoid\Box\Box;
use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Metadata\External\External;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Tasks\Extend\Extend;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Reflex\Test\Wrapper;

class ExtendTest extends Wrapper
{
    public function testCurrentStateRefresh(): void
    {
        $box = $this->createMock(Box::class);
        $group = $this->createMock(Group::class);
        $directory = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $log = $this->createMock(Log::class);
        $internal = $this->createMock(Internal::class);
        $content = $this->createMock(Content::class);
        $task = new Extend(
            box: $box,
            group: $group,
            log: $log,
            directory: $directory,
            file: $file,
            config: []);

        $group->fake("getExternalRootMetadata")
            ->return(null)
            ->fake("getInternalRootMetadata")
            ->return($internal)
            ->fake("hasDownloadable")
            ->return(false)
            ->fake("getInternalMetas")
            ->return([
                "i0" => $internal,
                "i1/i1" => $internal,
            ])
            ->fake("getImplication")
            ->return(["i1/i1" => [
                "implication" => []
            ]]);

        $internal->fake("getId")
            ->return("i0")
            ->fake("getCategory")
            ->return(InternalCategory::RECYCLABLE)
            ->repeat(1)
            ->fake("getContent")
            ->return(["#i0c"])
            ->return(["#i1c"])
            ->fake("getDir")
            ->return("/d0") // ":i1/i1/ex00"
            ->repeat(1) // ":i1/i1/ex00"
            ->fake("getSource")
            ->return("/s0")
            ->repeat(1)
            ->return("/s0/deps/s1")
            ->repeat(1)
            ->fake("getExtendablePaths")
            ->return([])
            ->return(["/ex00"])
            ->fake("getStatefulPath")
            ->return("/state")
            ->repeat(3)
            ->fake("getStructureMappings")
            ->return([
                "/##" => ":i1/i1/ex00",
                "/####" => ":i1/i1/ex00"
            ])
            ->return([]);

        $log->fake("info")
            ->return(null)
            ->repeat(2);

        $box->fake("get")
            ->expect(class: Content::class, arguments: ["content" => ["#i0c"]])
            ->return($content)
            ->expect(class: Content::class, arguments: ["content" => ["#i1c"]]);

        $directory->fake("getRootDir")
            ->return("#")
            ->fake("createDir")
            ->expect(dir: "/s0/state")
            ->expect(dir: "/s0/deps/s1/state");

        $file->fake("put")
            ->expect(file: "/s0/state/extensions.php", data: "<?php return [\n];")
            ->return(1)
            ->expect(file: "/s0/deps/s1/state/extensions.php", data: "<?php return [" .
                "\n\t\"/ex00\" => [" .
                "\n\t\t1 => dirname(__DIR__, 4) . \"/d0/####\"," . // 1:1 mapping
                "\n\t]," .
                "\n];");

        $task->execute();
    }

    public function testNewState(): void
    {
        $box = $this->createMock(Box::class);
        $group = $this->createMock(Group::class);
        $directory = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $log = $this->createMock(Log::class);
        $internal = $this->createMock(Internal::class);
        $external = $this->createMock(External::class);
        $content = $this->createMock(Content::class);
        $task = new Extend(
            box: $box,
            group: $group,
            log: $log,
            directory: $directory,
            file: $file,
            config: []);

        $group->fake("getExternalRootMetadata")
            ->return($external)
            ->fake("getInternalRootMetadata")
            ->return($internal)
            ->fake("hasDownloadable")
            ->return(true)
            ->fake("getExternalMetas")
            ->return([
                "i0" => $external,
                "i1" => $external,
                "i2" => $external
            ])
            ->fake("getImplication")
            ->return([
                "i0" => [
                    "implication" => [
                        "i1" => [
                            "implication" => []
                        ],
                        "i2" => [
                            "implication" => [
                                "i1" => [
                                    "implication" => []
                                ]
                            ]
                        ],
                    ]
                ]
            ]);

        $log->fake("info")
            ->return(null)
            ->repeat(3);

        $directory->fake("getRootDir")
            ->return("#")
            ->repeat(3)
            ->fake("getPackagesDir")
            ->return("/p")
            ->fake("createDir")
            ->expect(dir: "/p/i0/state")
            ->expect(dir: "/p/i1/state")
            ->expect(dir: "/p/i2/state");

        $internal->fake("getCategory")
            ->return(InternalCategory::OBSOLETE);

        $external->fake("getCategory")
            ->return(ExternalCategory::DOWNLOADABLE)
            ->fake("getDir")
            ->return("")
            ->repeat(3)
            ->return("/deps/i1")
            ->repeat(1)
            ->return("/deps/i2")
            ->repeat(3)
            ->fake("getExtendablePaths")
            ->return([])
            ->return(["/ex0"])
            ->return([])
            ->fake("getStatefulPath")
            ->return("/state")
            ->repeat(5)
            ->fake("getContent")
            ->return(["#i0c"])
            ->return(["#i1c"])
            ->return(["#i2c"])
            ->fake("getStructureMappings")
            ->return([
                "/###i0" => ":i1/ex",
                "/###i00" => ":i1/ex0"
            ])
            ->return([])
            ->return([
                "/###i2" => ":i1/ex",
                "/###i22" => ":i1/ex0"
            ]);

        $box->fake("get")
            ->expect(class: Content::class, arguments: ["content" => ["#i0c"]])
            ->return($content)
            ->expect(class: Content::class, arguments: ["content" => ["#i1c"]])
            ->expect(class: Content::class, arguments: ["content" => ["#i2c"]]);

        $file->fake("put")
            ->return(1)
            ->expect(file: "/p/i0/state/extensions.php",
                data: "<?php return [\n];")
            ->expect(file: "/p/i1/state/extensions.php",
                data: "<?php return [" .
                "\n\t\"/ex0\" => [" .
                "\n\t\t2 => dirname(__DIR__, 3) . \"/deps/i2/###i22\"," .
                "\n\t\t3 => dirname(__DIR__, 3) . \"/###i00\"," .
                "\n\t]," .
                "\n];")
            ->expect(file: "/p/i2/state/extensions.php",
                data: "<?php return [\n];");

        $task->execute();
    }

    public function testNewStateWithRecycledRoot(): void
    {
        $box = $this->createMock(Box::class);
        $group = $this->createMock(Group::class);
        $directory = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $log = $this->createMock(Log::class);
        $internal = $this->createMock(Internal::class);
        $external = $this->createMock(External::class);
        $content = $this->createMock(Content::class);
        $task = new Extend(
            box: $box,
            group: $group,
            log: $log,
            directory: $directory,
            file: $file,
            config: []);

        $group->fake("getExternalRootMetadata")
            ->return($external)
            ->fake("getInternalRootMetadata")
            ->return($internal)
            ->fake("hasDownloadable")
            ->return(true)
            ->fake("getExternalMetas")
            ->return([
                "i0" => $external,
                "i1" => $external,
                "i2" => $external
            ])
            ->fake("getImplication")
            ->return([
                "i0" => [
                    "implication" => [
                        "i1" => [
                            "implication" => []
                        ],
                        "i2" => [
                            "implication" => [
                                "i1" => [
                                    "implication" => []
                                ]
                            ]
                        ],
                    ]
                ]
            ]);

        $log->fake("info")
            ->return(null)
            ->repeat(2);

        $directory->fake("getRootDir")
            ->return("#")
            ->repeat(3)
            ->fake("getPackagesDir")
            ->return("/p")
            ->fake("createDir")
            ->expect(dir: "/p/i0/state")
            ->expect(dir: "/p/i1/state")
            ->expect(dir: "/p/i2/state");

        $internal->fake("getCategory")
            ->return(InternalCategory::RECYCLABLE)
            ->fake("getId")
            ->return("i0")
            ->fake("getDir")
            ->return("")
            ->repeat(2)
            ->fake("getStatefulPath")
            ->return("/state")
            ->repeat(1)
            ->fake("getExtendablePaths")
            ->return([])
            ->fake("getStructureMappings")
            ->return([
                "/###i0" => ":i1/ex",
                "/###i00" => ":i1/ex0"
            ]);

        $external->fake("getCategory")
            ->return(ExternalCategory::REDUNDANT)
            ->fake("getDir")
            ->return("")
            ->return("/deps/i1")
            ->repeat(1)
            ->return("/deps/i2")
            ->repeat(3)
            ->fake("getExtendablePaths")
            ->return(["/ex0"])
            ->return([])
            ->fake("getStatefulPath")
            ->return("/state")
            ->repeat(3)
            ->fake("getContent")
            ->return(["#i1c"])
            ->return(["#i2c"])
            ->fake("getStructureMappings")
            ->return([])
            ->return([
                "/###i2" => ":i1/ex",
                "/###i22" => ":i1/ex0"
            ]);

        $box->fake("get")
            ->return($content)
            ->expect(class: Content::class, arguments: ["content" => ["#i1c"]])
            ->expect(class: Content::class, arguments: ["content" => ["#i2c"]]);

        $file->fake("put")
            ->return(1)
            ->expect(file: "/p/i0/state/extensions.php",
                data: "<?php return [\n];")
            ->expect(file: "/p/i1/state/extensions.php",
                data: "<?php return [" .
                "\n\t\"/ex0\" => [" .
                "\n\t\t2 => dirname(__DIR__, 3) . \"/deps/i2/###i22\"," .
                "\n\t\t3 => dirname(__DIR__, 3) . \"/###i00\"," .
                "\n\t]," .
                "\n];")
            ->expect(file: "/p/i2/state/extensions.php",
                data: "<?php return [\n];");

        $task->execute();
    }
}