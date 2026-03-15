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

namespace Valvoid\Fusion\Tests\Units\Tasks\Replicate;

use Valvoid\Box\Box;
use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Hub\Hub;
use Valvoid\Fusion\Hub\Responses\Cache\Metadata;
use Valvoid\Fusion\Hub\Responses\Cache\Snapshot;
use Valvoid\Fusion\Hub\Responses\Cache\Versions;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Metadata\External\Builder;
use Valvoid\Fusion\Metadata\External\External;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tasks\Replicate\Replicate;
use Valvoid\Fusion\Wrappers\Extension;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Reflex\Test\Wrapper;

class ReplicateTest extends Wrapper
{
    public function testSourceSnapshot(): void
    {
        $box = $this->createMock(Box::class);
        $log = $this->createStub(Log::class);
        $hub = $this->createMock(Hub::class);
        $group = $this->createMock(Group::class);
        $extension = $this->createMock(Extension::class);
        $directory = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $builder = $this->createMock(Builder::class);
        $versions = $this->createStub(Versions::class);
        $metadata = $this->createStub(Metadata::class);
        $snapshot = $this->createStub(Snapshot::class);
        $external = $this->createStub(External::class);
        $content = $this->createStub(Content::class);
        $task = new Replicate(
            box: $box,
            group: $group,
            hub: $hub,
            directory: $directory,
            extension: $extension,
            log: $log,
            file: $file,
            config: [
                "source" => "#m0",
                "environment" => [
                    "php" => [
                        "version" => [
                            "major" => 8,
                            "minor" => 1,
                            "patch" => 0,
                            "build" => "",
                            "release" => ""
                        ]
                    ]
                ]
            ]);

        $log->fake("info")
            ->return(null)
            ->repeat(6);

        $extension->fake("getLoaded")
            ->return([])
            ->repeat(1);

        $group->fake("setImplicationBreadcrumb")
            ->expect(breadcrumb: ["replicate", "source"])
            ->fake("setImplication")
            ->expect(implication: [
                "#m0" => [
                    "source" => "#m0",
                    "implication" => [
                        "#m1" => [
                            "source" => "#m1",
                            "implication" => []
                        ]
                    ]
                ]

            ])
            ->repeat(1)
            ->fake("setExternalMetas")
            ->expect(metas: [
                "#m0" => $external,
                "#m1" => $external
            ])
            ->repeat(1);

        $box->fake("get")
            ->expect(class: Builder::class, arguments: ["source" => "#m0", "dir" => ""])
            ->return($builder)
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->return($content)
            ->expect(class: Builder::class, arguments: ["source" => "#m1", "dir" => "#d0"])
            ->return($builder)
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->return($content);

        $builder->fake("getParsedSource")
            ->return(["#m0"])
            ->fake("normalizeReference")
            ->expect(reference: "2.30.1")
            ->expect(reference: "1.2.3") // #m1
            ->fake("getNormalizedSource")
            ->return(["source" => "#m0", "version" => "2.30.1"])
            ->return(["source" => "#m1", "version" => "1.2.3"])
            ->fake("addProductionLayer")
            ->expect(content: "#m0c0", file: "#m0f0")
            ->expect(content: "#m1c0", file: "#m1f0")
            ->fake("getMetadata")
            ->return($external)
            ->repeat(1)
            ->fake("getId")
            ->return("#m1") // snap
            ->return("#m0") // implication order
            ->return("#m1")
            ->fake("getRawDir")
            ->return("#d0");

        $external->fake("getId") // structure order
            ->return("#m0")
            ->return("#m1")
            ->fake("getEnvironment")
            ->return([
                "php" => [
                    "modules" => [],
                    "version" => [[
                        "major" => "8",
                        "minor" => "1",
                        "patch" => "0",
                        "build" => "",
                        "release" => "",
                        "sign" => ""
                    ]]
                ]
            ])
            ->repeat(1)
            ->fake("getContent")
            ->return(["###"])
            ->repeat(1)
            ->fake("getSource")
            ->return(["#m0"])
            ->fake("getStatefulPath")
            ->return("/c0")
            ->fake("getStructureSources")
            ->return(["#d0" => ["#m1"]])
            ->return([]); // #m1

        $versions->fake("getTopEntry")
            ->return("2.30.1");

        $hub->fake("addVersionsRequest")
            ->expect(source: ["#m0"])
            ->return(0)
            ->fake("executeRequests")
            ->hook(fn ($callback) => $callback($versions)) // #m0
            ->hook(fn ($callback) => $callback($metadata)) // #m0
            ->hook(fn ($callback) => $callback($snapshot)) // #m0
            ->hook(function ($callback) use ($metadata) { // async hub loop
                $callback($metadata); // #m1
            })
            ->fake("addMetadataRequest")
            ->expect(source: ["source" => "#m0", "version" => "2.30.1"])
            ->return(1)
            ->expect(source: ["source" => "#m1", "version" => "1.2.3"])
            ->return(3)
            ->fake("addSnapshotRequest")
            ->expect(source: ["#m0"])
            ->return(2);

        $snapshot->fake("getContent")
            ->return('{"#m1": "1.2.3"}')
            ->fake("getFile")
            ->return("###");

        $metadata->fake("getContent")
            ->return("#m0c0")
            ->return("#m1c0")
            ->fake("getFile")
            ->return("#m0f0")
            ->return("#m1f0")
            ->fake("getId")
            ->return(3);

        $task->execute();
    }

    public function testCachedSnapshotFiles(): void
    {
        $box = $this->createMock(Box::class);
        $log = $this->createStub(Log::class);
        $hub = $this->createMock(Hub::class);
        $group = $this->createMock(Group::class);
        $extension = $this->createMock(Extension::class);
        $directory = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $builder = $this->createMock(Builder::class);
        $metadata = $this->createStub(Metadata::class);
        $external = $this->createStub(External::class);
        $internal = $this->createStub(Internal::class);
        $content = $this->createStub(Content::class);
        $task = new Replicate(
            box: $box,
            group: $group,
            hub: $hub,
            directory: $directory,
            extension: $extension,
            log: $log,
            file: $file,
            config: [
                "source" => false,
                "environment" => [
                    "php" => [
                        "version" => [
                            "major" => 8,
                            "minor" => 1,
                            "patch" => 0,
                            "build" => "",
                            "release" => ""
                        ]
                    ]
                ]
            ]);

        $log->fake("info")
            ->return(null)
            ->repeat(6);

        $extension->fake("getLoaded")
            ->return([])
            ->repeat(3);

        $group->fake("getInternalRootMetadata")
            ->return($internal)
            ->fake("setImplication")
            ->expect(implication: [
                "#m1" => [
                    "source" => "#m1",
                    "implication" => [
                        "#m4" => [
                            "source" => "#m4",
                            "implication" => []
                        ]
                    ]
                ],
                "#m2" => [
                    "source" => "#m2",
                    "implication" => []
                ],
                "#m3" => [
                    "source" => "#m3",
                    "implication" => []
                ]
            ])
            ->repeat(1)
            ->fake("setExternalMetas")
            ->expect(metas: [
                "#m1" => $external,
                "#m2" => $external,
                "#m3" => $external,
                "#m4" => $external
            ])
            ->repeat(1);

        $internal->fake("getStructureSources")
            ->return(["#d0" => ["#m1", "#m2", "#m3"]])
            ->repeat(1);

        $directory->fake("getStatefulDir")
            ->return("#")
            ->repeat(2);

        $file->fake("exists")
            ->expect(file: "#/snapshot.json")
            ->return(true)
            ->expect(file: "#/snapshot.dev.json")
            ->expect(file: "#/snapshot.local.json")
            ->fake("get")
            ->expect(file: "#/snapshot.json")
            ->return('{"#m1": "1.0.0"}')
            ->expect(file: "#/snapshot.dev.json")
            ->return('{"#m2": "2.0.0", "#m4": "0.5.0-beta"}')
            ->expect(file: "#/snapshot.local.json")
            ->return('{"#m3": "1.2.3"}');

        $box->fake("get")
            ->expect(class: Builder::class, arguments: ["source" => "#m1", "dir" => "#d0"])
            ->return($builder)
            ->expect(class: Builder::class, arguments: ["source" => "#m2", "dir" => "#d0"])
            ->expect(class: Builder::class, arguments: ["source" => "#m3", "dir" => "#d0"])
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->return($content)
            ->expect(class: Builder::class, arguments: ["source" => "#m4", "dir" => "#d0"])
            ->return($builder)
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->return($content)
            ->repeat(2);

        $builder->fake("normalizeReference")
            ->expect(reference: "1.0.0") // #m1
            ->expect(reference: "0.5.0-beta") // #m4
            ->expect(reference: "2.0.0") // #m2
            ->expect(reference: "1.2.3") // #m3
            ->fake("getNormalizedSource")
            ->return(["source" => "#m1", "version" => "1.0.0"])
            ->return(["source" => "#m4", "version" => "0.5.0-beta"])
            ->return(["source" => "#m2", "version" => "2.0.0"])
            ->return(["source" => "#m3", "version" => "1.2.3"])
            ->fake("addProductionLayer")
            ->expect(content: "#m1c0", file: "#m1f0")
            ->expect(content: "#m4c0", file: "#m4f0")
            ->expect(content: "#m2c0", file: "#m2f0")
            ->expect(content: "#m3c0", file: "#m3f0")
            ->fake("getMetadata")
            ->return($external)
            ->repeat(3)
            ->fake("getId")
            ->return("#m1")
            ->return("#m4")
            ->return("#m2")
            ->return("#m3")
            ->return("#m1") // implication start
            ->return("#m4")
            ->return("#m2")
            ->return("#m3")
            ->fake("getRawDir")
            ->return("#d0")
            ->repeat(3);

        $external->fake("getId") // structure order
            ->return("#m1")
            ->return("#m2")
            ->return("#m3")
            ->return("#m4")
            ->fake("getEnvironment")
            ->return([
                "php" => [
                    "modules" => [],
                    "version" => [[
                        "major" => "8",
                        "minor" => "1",
                        "patch" => "0",
                        "build" => "",
                        "release" => "",
                        "sign" => ""
                    ]]
                ]
            ])
            ->repeat(3)
            ->fake("getContent")
            ->return(["###"])
            ->repeat(5)
            ->fake("getStructureSources")
            ->return(["#d1" => ["#m4"]])
            ->return([])
            ->repeat(2);

        $hub->fake("executeRequests")
            ->hook(function ($callback) use ($metadata) { // async hub loop
                $callback($metadata); // #m1
                $callback($metadata); // #m4
                $callback($metadata); // #m2
                $callback($metadata); // #m3
            })
            ->fake("addMetadataRequest")
            ->expect(source: ["source" => "#m1", "version" => "1.0.0"])
            ->return(0)
            ->expect(source: ["source" => "#m4", "version" => "0.5.0-beta"])
            ->return(3)
            ->expect(source: ["source" => "#m2", "version" => "2.0.0"])
            ->return(1)
            ->expect(source: ["source" => "#m3", "version" => "1.2.3"])
            ->return(2);

        $metadata->fake("getContent")
            ->return("#m1c0")
            ->return("#m4c0")
            ->return("#m2c0")
            ->return("#m3c0")
            ->fake("getFile")
            ->return("#m1f0")
            ->return("#m4f0")
            ->return("#m2f0")
            ->return("#m3f0")
            ->fake("getId")
            ->return(0)
            ->return(0)
            ->return(1)
            ->return(2);

        $task->execute();
    }
}