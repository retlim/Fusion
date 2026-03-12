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

namespace Valvoid\Fusion\Tests\Units\Tasks\Build;

use Valvoid\Box\Box;
use Valvoid\Fusion\Hub\Hub;
use Valvoid\Fusion\Hub\Responses\Cache\Metadata;
use Valvoid\Fusion\Hub\Responses\Cache\Versions;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Metadata\External\Builder;
use Valvoid\Fusion\Metadata\External\External;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Tasks\Build\Build;
use Valvoid\Fusion\Tasks\Build\SAT\Solver;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Wrappers\Extension;
use Valvoid\Reflex\Test\Wrapper;

class BuildTest extends Wrapper
{
    public function testExternalRootSourceImplication(): void
    {
        $box = $this->createMock(Box::class);
        $log = $this->createStub(Log::class);
        $hub = $this->createMock(Hub::class);
        $group = $this->createMock(Group::class);
        $extension = $this->createMock(Extension::class);
        $solver = $this->createMock(Solver::class);
        $builder = $this->createMock(Builder::class);
        $versions = $this->createStub(Versions::class);
        $metadata = $this->createStub(Metadata::class);
        $external = $this->createStub(External::class);
        $content = $this->createStub(Content::class);
        $task = new Build(
            box: $box,
            group: $group,
            hub: $hub,
            extension: $extension,
            log: $log,
            config: [
                "source" => "#m0", // runtime layer
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

        $extension->fake("getLoaded")
            ->return([])
            ->repeat(4);

        $log->fake("info")
            ->return(null)
            ->repeat(6);

        $group->fake("setImplicationBreadcrumb")
            ->expect(breadcrumb: ["built", "source"])
            ->fake("setExternalMetas")
            ->expect(metas: [
                "#m0" => $external,
                "#m1" => $external,
                "#m2" => $external,
                "#m3" => $external,
                "#m4" => $external])
            ->fake("setImplication")
            ->expect(implication: [
                "#m0" => [
                    "source" => "#m0",
                    "implication" => [
                        "#m1" => [
                            "source" => "#m1",
                            "implication" => []
                        ],
                        "#m2" => [
                            "source" => "#m2",
                            "implication" => [
                                "#m4" => [
                                    "source" => "#m4",
                                    "implication" => []
                                ]
                            ]
                        ],
                        "#m3" => [
                            "source" => "#m3",
                            "implication" => []
                        ]
                    ]
                ]
            ]);

        $box->fake("get")
            ->expect(class: Builder::class, arguments: ["source" => "#m0", "dir" => ""])
            ->return($builder)
            ->expect(class: Builder::class, arguments: ["source" => "#m1", "dir" => "#d0"])
            ->expect(class: Builder::class, arguments: ["source" => "#m2", "dir" => "#d0"])
            ->expect(class: Builder::class, arguments: ["source" => "#m3", "dir" => "#d1"])
            ->expect(class: Builder::class, arguments: ["source" => "#m4", "dir" => "#d0"])
            ->repeat(2)
            ->expect(class: Solver::class, arguments: ["id" => "#m0", "version" => "1.0.0",
                "implication" => [
                "#m1" => [
                    "source" => "#m1",
                    "implication" => ["1.0.0" => []]
                ],
                "#m2" => [
                    "source" => "#m2",
                    "implication" => [
                        "2.30.1" => [
                            "#m4" => [
                                "source" => "#m4",
                                "implication" => ["1.0.0" => []]
                            ]
                        ],
                        "2.0.0:offset" => [
                            "#m4" => [
                                "source" => "#m4",
                                "implication" => ["1.0.0" => []]
                            ]
                        ],
                        "1.0.0" => [
                            "#m4" => [
                                "source" => "#m4",
                                "implication" => ["1.0.0" => []]
                            ]
                        ]
                    ]
                ],
                "#m3" => [
                    "source"=> "#m3",
                    "implication"=> ["1.0.0"=> []]
                ]

            ]])
            ->return($solver)
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->return($content)
            ->repeat(4);

        $builder->fake("getParsedSource")
            ->return(["#m0"])
            ->return(["#m1"])
            ->return(["#m2"])
            ->return(["#m3"])
            ->return(["#m4"])
            ->repeat(2)
            ->fake("normalizeReference")
            ->expect(reference: "1.0.0")
            ->expect(reference: "1.0.0")
            ->expect(reference: "2.30.1")
            ->expect(reference: "2.0.0:offset")
            ->expect(reference: "1.0.0")
            ->repeat(4)
            ->fake("getNormalizedSource")
            ->return(["source" => "#m0", "version" => "1.0.0"])
            ->return(["source" => "#m1", "version" => "1.0.0"])
            ->return(["source" => "#m2", "version" => "2.30.1"])
            ->return(["source" => "#m2", "version" => "2.0.0:offset"])
            ->return(["source" => "#m2", "version" => "1.0.0"])
            ->return(["source" => "#m3", "version" => "1.0.0"])
            ->return(["source" => "#m4", "version" => "1.0.0"])
            ->repeat(2)
            ->fake("addProductionLayer")
            ->expect(content: "#m0c0", file: "#m0f0")
            ->expect(content: "#m1c0", file: "#m1f0")
            ->expect(content: "#m2c0", file: "#m2f0")
            ->expect(content: "#m2c1", file: "#m2f1")
            ->expect(content: "#m2c2", file: "#m2f2")
            ->expect(content: "#m3c0", file: "#m3f0")
            ->expect(content: "#m4c0", file: "#m4f0")
            ->repeat(2)
            ->fake("getMetadata")
            ->return($external)
            ->repeat(8)
            ->fake("getRawDir")
            ->return("#d0")
            ->repeat(3)
            ->return("#d1")
            ->return("#d0")
            ->repeat(2);

        $external->fake("getStructureSources")
            ->return(["#d0" => ["#m1", "#m2"], "#d1" => ["#m3"]])
            ->return([]) // #m1
            ->return(["#d2" => ["#m4"]]) // #m2 - 3 versions
            ->repeat(2)
            ->return([]) // #m3
            ->repeat(3) // #m4
            ->fake("getId") // structure order
            ->return("#m0")
            ->return("#m1")
            ->return("#m2")
            ->return("#m4")
            ->return("#m2")
            ->return("#m4")
            ->return("#m2")
            ->return("#m4")
            ->return("#m3")
            ->return("#m0")
            ->fake("getVersion") // structure order
            ->return("1.0.0")
            ->return("1.0.0")
            ->return("2.30.1")
            ->return("1.0.0")
            ->return("2.0.0:offset")
            ->return("1.0.0")
            ->repeat(4)
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
            ->repeat(4)
            ->fake("getContent")
            ->return(["###"])
            ->repeat(4);

        $hub->fake("addVersionsRequest")
            ->expect(source: ["#m0"])
            ->return(0)
            ->expect(source: ["#m1"])
            ->return(2)
            ->expect(source: ["#m2"])
            ->return(3)
            ->expect(source: ["#m3"])
            ->return(4)
            ->expect(source: ["#m4"])
            ->return(10)
            ->expect(source: ["#m4"])
            ->return(11)
            ->expect(source: ["#m4"])
            ->return(12)
            ->fake("executeRequests")
            ->hook(fn ($callback) => $callback($versions)) // #m0
            ->hook(fn ($callback) => $callback($metadata)) // #m0
            ->hook(function ($callback) use ($versions, $metadata) { // async hub loop
                $callback($versions); // #m1
                $callback($metadata);

                $callback($versions); // #m2
                $callback($metadata);
                $callback($metadata);
                $callback($metadata);

                $callback($versions); // #m3
                $callback($metadata);

                $callback($versions); // #m4
                $callback($metadata);
                $callback($versions);
                $callback($metadata);
                $callback($versions);
                $callback($metadata);
            })
            ->fake("addMetadataRequest")
            ->expect(source: ["source" => "#m0", "version" => "1.0.0"])
            ->return(1)
            ->expect(source: ["source" => "#m1", "version" => "1.0.0"])
            ->return(5)
            ->expect(source: ["source" => "#m2", "version" => "2.30.1"])
            ->return(6)
            ->expect(source: ["source" => "#m2", "version" => "2.0.0:offset"])
            ->return(7)
            ->expect(source: ["source" => "#m2", "version" => "1.0.0"])
            ->return(8)
            ->expect(source: ["source" => "#m3", "version" => "1.0.0"])
            ->return(9)
            ->expect(source: ["source" => "#m4", "version" => "1.0.0"])
            ->return(13)
            ->expect(source: ["source" => "#m4", "version" => "1.0.0"])
            ->return(14)
            ->expect(source: ["source" => "#m4", "version" => "1.0.0"])
            ->return(15);

        $versions->fake("getEntries")
            ->return(["1.0.0"])
            ->return(["1.0.0"])
            ->return(["2.30.1", "2.0.0:offset", "1.0.0"])
            ->return(["1.0.0"])
            ->repeat(3)
            ->fake("getId")
            ->return(2)
            ->return(3)
            ->return(4)
            ->return(10)
            ->return(11)
            ->return(12);

        $metadata->fake("getContent")
            ->return("#m0c0")
            ->return("#m1c0")
            ->return("#m2c0")
            ->return("#m2c1")
            ->return("#m2c2")
            ->return("#m3c0")
            ->return("#m4c0")
            ->repeat(2)
            ->fake("getFile")
            ->return("#m0f0")
            ->return("#m1f0")
            ->return("#m2f0")
            ->return("#m2f1")
            ->return("#m2f2")
            ->return("#m3f0")
            ->return("#m4f0")
            ->repeat(2)
            ->fake("getId")
            ->return(5)
            ->return(6)
            ->return(7)
            ->return(8)
            ->return(9)
            ->return(13)
            ->return(14)
            ->return(15);

        $solver->fake("isStructureSatisfiable")
            ->return(true)
            ->fake("getPath")
            ->return([
                "#m0" => "1.0.0",
                "#m1" => "1.0.0",
                "#m2" => "2.30.1",
                "#m3" => "1.0.0",
                "#m4" => "1.0.0"
            ]);

        $task->execute();
    }

    public function testRecursiveMetadataImplication(): void
    {
        $box = $this->createMock(Box::class);
        $log = $this->createStub(Log::class);
        $hub = $this->createMock(Hub::class);
        $group = $this->createMock(Group::class);
        $extension = $this->createMock(Extension::class);
        $solver = $this->createMock(Solver::class);
        $builder = $this->createMock(Builder::class);
        $versions = $this->createStub(Versions::class);
        $metadata = $this->createStub(Metadata::class);
        $external = $this->createStub(External::class);
        $internal = $this->createStub(Internal::class);
        $content = $this->createStub(Content::class);
        $task = new Build(
            box: $box,
            group: $group,
            hub: $hub,
            extension: $extension,
            log: $log,
            config: [
                "source" => false, // runtime layer
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

        $internal->fake("getStructureSources")
            ->return(["" => ["#m0"]]);

        $extension->fake("getLoaded")
            ->return([])
            ->repeat(4);

        $log->fake("info")
            ->return(null)
            ->repeat(6);

        $group->fake("getInternalRootMetadata")
            ->return($internal)
            ->fake("setExternalMetas")
            ->expect(metas: [
                "#m0" => $external,
                "#m1" => $external,
                "#m2" => $external,
                "#m3" => $external,
                "#m4" => $external])
            ->fake("setImplication")
            ->expect(implication: [
                "#m0" => [
                    "source" => "#m0",
                    "implication" => [
                        "#m1" => [
                            "source" => "#m1",
                            "implication" => []
                        ],
                        "#m2" => [
                            "source" => "#m2",
                            "implication" => [
                                "#m4" => [
                                    "source" => "#m4",
                                    "implication" => []
                                ]
                            ]
                        ],
                        "#m3" => [
                            "source" => "#m3",
                            "implication" => []
                        ]
                    ]
                ]
            ]);

        $box->fake("get")
            ->expect(class: Builder::class, arguments: ["source" => "#m0", "dir" => ""])
            ->return($builder)
            ->expect(class: Builder::class, arguments: ["source" => "#m1", "dir" => "#d0"])
            ->expect(class: Builder::class, arguments: ["source" => "#m2", "dir" => "#d0"])
            ->expect(class: Builder::class, arguments: ["source" => "#m3", "dir" => "#d1"])
            ->expect(class: Builder::class, arguments: ["source" => "#m4", "dir" => "#d0"])
            ->repeat(2)
            ->expect(class: Solver::class, arguments: ["id" => "#m0", "version" => "1.0.0",
                "implication" => [
                    "#m1" => [
                        "source" => "#m1",
                        "implication" => ["1.0.0" => []]
                    ],
                    "#m2" => [
                        "source" => "#m2",
                        "implication" => [
                            "2.30.1" => [
                                "#m4" => [
                                    "source" => "#m4",
                                    "implication" => ["1.0.0" => []]
                                ]
                            ],
                            "2.0.0:offset" => [
                                "#m4" => [
                                    "source" => "#m4",
                                    "implication" => ["1.0.0" => []]
                                ]
                            ],
                            "1.0.0" => [
                                "#m4" => [
                                    "source" => "#m4",
                                    "implication" => ["1.0.0" => []]
                                ]
                            ]
                        ]
                    ],
                    "#m3" => [
                        "source"=> "#m3",
                        "implication"=> ["1.0.0"=> []]
                    ]

                ]])
            ->return($solver)
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->return($content)
            ->repeat(4);

        $builder->fake("getParsedSource")
            ->return(["#m0"])
            ->return(["#m1"])
            ->return(["#m2"])
            ->return(["#m3"])
            ->return(["#m4"])
            ->repeat(2)
            ->fake("normalizeReference")
            ->expect(reference: "1.0.0")
            ->expect(reference: "1.0.0")
            ->expect(reference: "2.30.1")
            ->expect(reference: "2.0.0:offset")
            ->expect(reference: "1.0.0")
            ->repeat(4)
            ->fake("getNormalizedSource")
            ->return(["source" => "#m0", "version" => "1.0.0"])
            ->return(["source" => "#m1", "version" => "1.0.0"])
            ->return(["source" => "#m2", "version" => "2.30.1"])
            ->return(["source" => "#m2", "version" => "2.0.0:offset"])
            ->return(["source" => "#m2", "version" => "1.0.0"])
            ->return(["source" => "#m3", "version" => "1.0.0"])
            ->return(["source" => "#m4", "version" => "1.0.0"])
            ->repeat(2)
            ->fake("addProductionLayer")
            ->expect(content: "#m0c0", file: "#m0f0")
            ->expect(content: "#m1c0", file: "#m1f0")
            ->expect(content: "#m2c0", file: "#m2f0")
            ->expect(content: "#m2c1", file: "#m2f1")
            ->expect(content: "#m2c2", file: "#m2f2")
            ->expect(content: "#m3c0", file: "#m3f0")
            ->expect(content: "#m4c0", file: "#m4f0")
            ->repeat(2)
            ->fake("getMetadata")
            ->return($external)
            ->repeat(8)
            ->fake("getRawDir")
            ->return("#d0")
            ->repeat(3)
            ->return("#d1")
            ->return("#d0")
            ->repeat(2);

        $external->fake("getStructureSources")
            ->return(["#d0" => ["#m1", "#m2"], "#d1" => ["#m3"]])
            ->return([]) // #m1
            ->return(["#d2" => ["#m4"]]) // #m2 - 3 versions
            ->repeat(2)
            ->return([]) // #m3
            ->repeat(3) // #m4
            ->fake("getId") // structure order
            ->return("#m0")
            ->return("#m1")
            ->return("#m2")
            ->return("#m4")
            ->return("#m2")
            ->return("#m4")
            ->return("#m2")
            ->return("#m4")
            ->return("#m3")
            ->return("#m0")
            ->fake("getVersion") // structure order
            ->return("1.0.0")
            ->return("1.0.0")
            ->return("2.30.1")
            ->return("1.0.0")
            ->return("2.0.0:offset")
            ->return("1.0.0")
            ->repeat(4)
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
            ->repeat(4)
            ->fake("getContent")
            ->return(["###"])
            ->repeat(4);

        $hub->fake("addVersionsRequest")
            ->expect(source: ["#m0"])
            ->return(0)
            ->expect(source: ["#m1"])
            ->return(2)
            ->expect(source: ["#m2"])
            ->return(3)
            ->expect(source: ["#m3"])
            ->return(4)
            ->expect(source: ["#m4"])
            ->return(10)
            ->expect(source: ["#m4"])
            ->return(11)
            ->expect(source: ["#m4"])
            ->return(12)
            ->fake("executeRequests")
            ->hook(fn ($callback) => $callback($versions)) // #m0
            ->hook(fn ($callback) => $callback($metadata)) // #m0
            ->hook(function ($callback) use ($versions, $metadata) { // async hub loop
                $callback($versions); // #m1
                $callback($metadata);

                $callback($versions); // #m2
                $callback($metadata);
                $callback($metadata);
                $callback($metadata);

                $callback($versions); // #m3
                $callback($metadata);

                $callback($versions); // #m4
                $callback($metadata);
                $callback($versions);
                $callback($metadata);
                $callback($versions);
                $callback($metadata);
            })
            ->fake("addMetadataRequest")
            ->expect(source: ["source" => "#m0", "version" => "1.0.0"])
            ->return(1)
            ->expect(source: ["source" => "#m1", "version" => "1.0.0"])
            ->return(5)
            ->expect(source: ["source" => "#m2", "version" => "2.30.1"])
            ->return(6)
            ->expect(source: ["source" => "#m2", "version" => "2.0.0:offset"])
            ->return(7)
            ->expect(source: ["source" => "#m2", "version" => "1.0.0"])
            ->return(8)
            ->expect(source: ["source" => "#m3", "version" => "1.0.0"])
            ->return(9)
            ->expect(source: ["source" => "#m4", "version" => "1.0.0"])
            ->return(13)
            ->expect(source: ["source" => "#m4", "version" => "1.0.0"])
            ->return(14)
            ->expect(source: ["source" => "#m4", "version" => "1.0.0"])
            ->return(15);

        $versions->fake("getEntries")
            ->return(["1.0.0"])
            ->return(["1.0.0"])
            ->return(["2.30.1", "2.0.0:offset", "1.0.0"])
            ->return(["1.0.0"])
            ->repeat(3)
            ->fake("getId")
            ->return(2)
            ->return(3)
            ->return(4)
            ->return(10)
            ->return(11)
            ->return(12);

        $metadata->fake("getContent")
            ->return("#m0c0")
            ->return("#m1c0")
            ->return("#m2c0")
            ->return("#m2c1")
            ->return("#m2c2")
            ->return("#m3c0")
            ->return("#m4c0")
            ->repeat(2)
            ->fake("getFile")
            ->return("#m0f0")
            ->return("#m1f0")
            ->return("#m2f0")
            ->return("#m2f1")
            ->return("#m2f2")
            ->return("#m3f0")
            ->return("#m4f0")
            ->repeat(2)
            ->fake("getId")
            ->return(5)
            ->return(6)
            ->return(7)
            ->return(8)
            ->return(9)
            ->return(13)
            ->return(14)
            ->return(15);

        $solver->fake("isStructureSatisfiable")
            ->return(true)
            ->fake("getPath")
            ->return([
                "#m0" => "1.0.0",
                "#m1" => "1.0.0",
                "#m2" => "2.30.1",
                "#m3" => "1.0.0",
                "#m4" => "1.0.0"
            ]);

        $task->execute();
    }

    public function testNestedMetadataImplication(): void
    {
        $box = $this->createMock(Box::class);
        $log = $this->createStub(Log::class);
        $hub = $this->createMock(Hub::class);
        $group = $this->createMock(Group::class);
        $extension = $this->createMock(Extension::class);
        $solver = $this->createMock(Solver::class);
        $builder = $this->createMock(Builder::class);
        $versions = $this->createStub(Versions::class);
        $metadata = $this->createStub(Metadata::class);
        $external = $this->createStub(External::class);
        $internal = $this->createStub(Internal::class);
        $content = $this->createStub(Content::class);
        $task = new Build(
            box: $box,
            group: $group,
            hub: $hub,
            extension: $extension,
            log: $log,
            config: [
                "source" => false, // runtime layer
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

        $internal->fake("getStructureSources")
            ->return(["#d0" => ["#m1", "#m2"], "#d1" => ["#m3"]])
            ->repeat(1)
            ->fake("getId")
            ->return("#m0")
            ->fake("getVersion")
            ->return("1.0.0");

        $extension->fake("getLoaded")
            ->return([])
            ->repeat(3);

        $log->fake("info")
            ->return(null)
            ->repeat(6);

        $group->fake("getInternalRootMetadata")
            ->return($internal)
            ->fake("setExternalMetas")
            ->expect(metas: [
                "#m1" => $external,
                "#m2" => $external,
                "#m3" => $external,
                "#m4" => $external])
            ->fake("setImplication")
            ->expect(implication: [
                "#m1" => [
                    "source" => "#m1",
                    "implication" => []
                ],
                "#m2" => [
                    "source" => "#m2",
                    "implication" => [
                        "#m4" => [
                            "source" => "#m4",
                            "implication" => []
                        ]
                    ]
                ],
                "#m3" => [
                    "source" => "#m3",
                    "implication" => []
                ]
            ]);

        $box->fake("get")
            ->expect(class: Builder::class, arguments: ["source" => "#m1", "dir" => "#d0"])
            ->return($builder)
            ->expect(class: Builder::class, arguments: ["source" => "#m2", "dir" => "#d0"])
            ->expect(class: Builder::class, arguments: ["source" => "#m3", "dir" => "#d1"])
            ->expect(class: Builder::class, arguments: ["source" => "#m4", "dir" => "#d0"])
            ->repeat(2)
            ->expect(class: Solver::class, arguments: ["id" => "#m0", "version" => "1.0.0",
                "implication" => [
                    "#m1" => [
                        "source" => "#m1",
                        "implication" => ["1.0.0" => []]
                    ],
                    "#m2" => [
                        "source" => "#m2",
                        "implication" => [
                            "2.30.1" => [
                                "#m4" => [
                                    "source" => "#m4",
                                    "implication" => ["1.0.0" => []]
                                ]
                            ],
                            "2.0.0:offset" => [
                                "#m4" => [
                                    "source" => "#m4",
                                    "implication" => ["1.0.0" => []]
                                ]
                            ],
                            "1.0.0" => [
                                "#m4" => [
                                    "source" => "#m4",
                                    "implication" => ["1.0.0" => []]
                                ]
                            ]
                        ]
                    ],
                    "#m3" => [
                        "source"=> "#m3",
                        "implication"=> ["1.0.0"=> []]
                    ]

                ]])
            ->return($solver)
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->return($content)
            ->repeat(3);

        $builder->fake("getParsedSource")
            ->return(["#m1"])
            ->return(["#m2"])
            ->return(["#m3"])
            ->return(["#m4"])
            ->repeat(2)
            ->fake("normalizeReference")
            ->expect(reference: "1.0.0")
            ->expect(reference: "2.30.1")
            ->expect(reference: "2.0.0:offset")
            ->expect(reference: "1.0.0")
            ->repeat(4)
            ->fake("getNormalizedSource")
            ->return(["source" => "#m1", "version" => "1.0.0"])
            ->return(["source" => "#m2", "version" => "2.30.1"])
            ->return(["source" => "#m2", "version" => "2.0.0:offset"])
            ->return(["source" => "#m2", "version" => "1.0.0"])
            ->return(["source" => "#m3", "version" => "1.0.0"])
            ->return(["source" => "#m4", "version" => "1.0.0"])
            ->repeat(2)
            ->fake("addProductionLayer")
            ->expect(content: "#m1c0", file: "#m1f0")
            ->expect(content: "#m2c0", file: "#m2f0")
            ->expect(content: "#m2c1", file: "#m2f1")
            ->expect(content: "#m2c2", file: "#m2f2")
            ->expect(content: "#m3c0", file: "#m3f0")
            ->expect(content: "#m4c0", file: "#m4f0")
            ->repeat(2)
            ->fake("getMetadata")
            ->return($external)
            ->repeat(7)
            ->fake("getRawDir")
            ->return("#d0")
            ->repeat(3)
            ->return("#d1")
            ->return("#d0")
            ->repeat(2);

        $external->fake("getStructureSources")
            ->return([]) // #m1
            ->return(["#d2" => ["#m4"]]) // #m2 - 3 versions
            ->repeat(2)
            ->return([]) // #m3
            ->repeat(3) // #m4
            ->fake("getId") // structure order
            ->return("#m1")
            ->return("#m2")
            ->return("#m4")
            ->return("#m2")
            ->return("#m4")
            ->return("#m2")
            ->return("#m4")
            ->return("#m3")
            ->fake("getVersion") // structure order
            ->return("1.0.0")
            ->return("2.30.1")
            ->return("1.0.0")
            ->return("2.0.0:offset")
            ->return("1.0.0")
            ->repeat(4)
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
            ->repeat(4)
            ->fake("getContent")
            ->return(["###"])
            ->repeat(4);

        $hub->fake("addVersionsRequest")
            ->expect(source: ["#m1"])
            ->return(2)
            ->expect(source: ["#m2"])
            ->return(3)
            ->expect(source: ["#m3"])
            ->return(4)
            ->expect(source: ["#m4"])
            ->return(10)
            ->expect(source: ["#m4"])
            ->return(11)
            ->expect(source: ["#m4"])
            ->return(12)
            ->fake("executeRequests")
            ->hook(function ($callback) use ($versions, $metadata) { // async hub loop
                $callback($versions); // #m1
                $callback($metadata);

                $callback($versions); // #m2
                $callback($metadata);
                $callback($metadata);
                $callback($metadata);

                $callback($versions); // #m3
                $callback($metadata);

                $callback($versions); // #m4
                $callback($metadata);
                $callback($versions);
                $callback($metadata);
                $callback($versions);
                $callback($metadata);
            })
            ->fake("addMetadataRequest")
            ->expect(source: ["source" => "#m1", "version" => "1.0.0"])
            ->return(5)
            ->expect(source: ["source" => "#m2", "version" => "2.30.1"])
            ->return(6)
            ->expect(source: ["source" => "#m2", "version" => "2.0.0:offset"])
            ->return(7)
            ->expect(source: ["source" => "#m2", "version" => "1.0.0"])
            ->return(8)
            ->expect(source: ["source" => "#m3", "version" => "1.0.0"])
            ->return(9)
            ->expect(source: ["source" => "#m4", "version" => "1.0.0"])
            ->return(13)
            ->expect(source: ["source" => "#m4", "version" => "1.0.0"])
            ->return(14)
            ->expect(source: ["source" => "#m4", "version" => "1.0.0"])
            ->return(15);

        $versions->fake("getEntries")
            ->return(["1.0.0"])
            ->return(["2.30.1", "2.0.0:offset", "1.0.0"])
            ->return(["1.0.0"])
            ->repeat(3)
            ->fake("getId")
            ->return(2)
            ->return(3)
            ->return(4)
            ->return(10)
            ->return(11)
            ->return(12);

        $metadata->fake("getContent")
            ->return("#m1c0")
            ->return("#m2c0")
            ->return("#m2c1")
            ->return("#m2c2")
            ->return("#m3c0")
            ->return("#m4c0")
            ->repeat(2)
            ->fake("getFile")
            ->return("#m1f0")
            ->return("#m2f0")
            ->return("#m2f1")
            ->return("#m2f2")
            ->return("#m3f0")
            ->return("#m4f0")
            ->repeat(2)
            ->fake("getId")
            ->return(5)
            ->return(6)
            ->return(7)
            ->return(8)
            ->return(9)
            ->return(13)
            ->return(14)
            ->return(15);

        $solver->fake("isStructureSatisfiable")
            ->return(true)
            ->fake("getPath")
            ->return([
                "#m1" => "1.0.0",
                "#m2" => "2.30.1",
                "#m3" => "1.0.0",
                "#m4" => "1.0.0"
            ]);

        $task->execute();
    }
}