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

namespace Valvoid\Fusion\Tests\Units\Tasks\Snap;

use Valvoid\Box\Box;
use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Metadata\External\External;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tasks\Snap\Snap;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Reflex\Test\Wrapper;

class SnapTest extends Wrapper
{
    public function testCurrentRecursiveState(): void
    {
        $box = $this->createMock(Box::class);
        $log = $this->createMock(Log::class);
        $directory = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $group = $this->createMock(Group::class);
        $external = $this->createMock(External::class);
        $content = $this->createStub(Content::class);
        $task = new Snap(
            box: $box,
            group: $group,
            log: $log,
            directory: $directory,
            file: $file,
            config: []
        );

        $log->fake("info")
            ->return(null)
            ->repeat(3)
            ->fake("verbose")
            ->return(null);

        $group->fake("getImplication")
            ->return([
                "i0" => [
                    "implication" => [
                        "i1" => ["implication" => []],
                        "i2" => ["implication" => []]
                    ]
                ]
            ])
            ->fake("hasDownloadable")
            ->return(false)
            ->fake("getExternalMetas")
            ->return([
                "i0" => $external,
                "i1" => $external,
                "i2" => $external
            ])
            ->fake("getRootMetadata")
            ->return($external);

        $directory->fake("getStatefulDir")
            ->return("/state")
            ->fake("createDir")
            ->expect(dir: "/state");

        $external->fake("getId")
            ->return("i0")
            ->fake("getProductionIds")
            ->return(["i1", "i2"])
            ->fake("getSource")
            ->return(["reference" => "offset"])
            ->return(["reference" => "1.2.3"])
            ->fake("getLayers")
            ->return(["object" => ["version" => "3.2.1"]])
            ->return([])
            ->fake("getContent")
            ->return(["###"])
            ->repeat(1);

        $box->fake("get")
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->return($content)
            ->repeat(1);

        $file->fake("put")
            ->return(1)
            ->expect(file: "/state/snapshot.json",
                data: "{\n" .
                "    \"i1\": \"3.2.1:offset\",\n" .
                "    \"i2\": \"1.2.3\"\n" .
                "}");

        $task->execute();
    }

    public function testNewRecursiveState(): void
    {
        $box = $this->createMock(Box::class);
        $log = $this->createMock(Log::class);
        $directory = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $group = $this->createMock(Group::class);
        $external = $this->createMock(External::class);
        $content = $this->createStub(Content::class);
        $task = new Snap(
            box: $box,
            group: $group,
            log: $log,
            directory: $directory,
            file: $file,
            config: []
        );

        $log->fake("info")
            ->return(null)
            ->repeat(3)
            ->fake("verbose")
            ->return(null);

        $group->fake("getImplication")
            ->return([
                "i0" => [
                    "implication" => [
                        "i1" => ["implication" => []],
                        "i2" => ["implication" => []]
                    ]
                ]
            ])
            ->fake("hasDownloadable")
            ->return(true)
            ->fake("getExternalMetas")
            ->return([
                "i0" => $external,
                "i1" => $external,
                "i2" => $external
            ])
            ->fake("getRootMetadata")
            ->return($external);

        $directory->fake("getPackagesDir")
            ->return("/tmp/packages")
            ->fake("createDir")
            ->expect(dir: "/tmp/packages/i0/state");

        $external->fake("getStatefulPath")
            ->return("/state")
            ->fake("getId")
            ->return("i0")
            ->fake("getProductionIds")
            ->return(["i1", "i2"])
            ->fake("getSource")
            ->return(["reference" => "offset"])
            ->return(["reference" => "1.2.3"])
            ->fake("getLayers")
            ->return(["object" => ["version" => "3.2.1"]])
            ->return([])
            ->fake("getContent")
            ->return(["###"])
            ->repeat(1);

        $box->fake("get")
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->return($content)
            ->repeat(1);

        $file->fake("put")
            ->return(1)
            ->expect(file: "/tmp/packages/i0/state/snapshot.json",
                data: "{\n" .
                "    \"i1\": \"3.2.1:offset\",\n" .
                "    \"i2\": \"1.2.3\"\n" .
                "}");

        $task->execute();
    }

    public function testDependencyState(): void
    {
        $box = $this->createMock(Box::class);
        $log = $this->createMock(Log::class);
        $directory = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $group = $this->createMock(Group::class);
        $internal = $this->createMock(Internal::class);
        $external = $this->createMock(External::class);
        $content = $this->createStub(Content::class);
        $task = new Snap(
            box: $box,
            group: $group,
            log: $log,
            directory: $directory,
            file: $file,
            config: []
        );

        $log->fake("info")
            ->return(null)
            ->repeat(4)
            ->fake("verbose")
            ->return(null)
            ->repeat(1);

        $group->fake("getImplication")
            ->return([
                "i1" => ["implication" => []],
                "i2" => ["implication" => []]
            ])
            ->fake("hasDownloadable")
            ->return(true)
            ->fake("getExternalMetas")
            ->return([
                "i1" => $external,
                "i2" => $external
            ])
            ->fake("getRootMetadata")
            ->return($internal);

        $directory->fake("getPackagesDir")
            ->return("/tmp/packages")
            ->fake("createDir")
            ->expect(dir: "/tmp/packages/i0/state")
            ->fake("delete")
            ->expect(file: "/tmp/packages/i0/state/snapshot.local.json");

        $external->fake("getSource")
            ->return(["reference" => "offset"])
            ->return(["reference" => "1.2.3"])
            ->fake("getLayers")
            ->return(["object" => ["version" => "3.2.1"]])
            ->return([])
            ->fake("getContent")
            ->return(["###"])
            ->repeat(1);

        $internal->fake("getId")
            ->return("i0")
            ->fake("getStatefulPath")
            ->return("/state")
            ->fake("getProductionIds")
            ->return(["i1"])
            ->fake("getLocalIds")
            ->return(null)
            ->fake("getDevelopmentIds")
            ->return(["i2"]);

        $box->fake("get")
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->return($content)
            ->repeat(1);

        $file->fake("put")
            ->return(1)
            ->expect(file: "/tmp/packages/i0/state/snapshot.json",
                data: "{\n" .
                "    \"i1\": \"3.2.1:offset\"\n" .
                "}")
            ->return(1)
            ->expect(file: "/tmp/packages/i0/state/snapshot.dev.json",
                data: "{\n" .
                "    \"i2\": \"1.2.3\"\n" .
                "}");

        $task->execute();
    }
}