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

namespace Valvoid\Fusion\Tests\Tasks\Build;

use Closure;
use Exception;
use Valvoid\Fusion\Hub\Responses\Cache\Metadata;
use Valvoid\Fusion\Hub\Responses\Cache\Versions;
use Valvoid\Fusion\Tasks\Build\Build;
use Valvoid\Fusion\Tests\Tasks\Build\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Build\Mocks\BuilderMock;
use Valvoid\Fusion\Tests\Tasks\Build\Mocks\ExtensionMock;
use Valvoid\Fusion\Tests\Tasks\Build\Mocks\ExternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Build\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Build\Mocks\HubMock;
use Valvoid\Fusion\Tests\Tasks\Build\Mocks\InternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Build\Mocks\LogMock;
use Valvoid\Fusion\Tests\Tasks\Build\Mocks\SolverMock;
use Valvoid\Fusion\Tests\Test;

/**
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class BuildTest extends Test
{
    protected string|array $coverage = Build::class;
    private BoxMock $box;
    private LogMock $log;
    private SolverMock $solver;
    private array $env = [
        "php" => [
            "version" => [
                "major" => 8,
                "minor" => 1,
                "patch" => 0,
                "build" => "",
                "release" => ""
            ]
        ]
    ];

    public function __construct()
    {
        $this->box = new BoxMock;
        $this->log = new LogMock;
        $this->solver = new SolverMock;

        $this->testExternalRootSourceImplication();
        $this->testRecursiveMetadataImplication();
        $this->testNestedMetadataImplication();

        $this->box::unsetInstance();
    }

    public function testExternalRootSourceImplication(): void
    {
        try {
            $hub = new HubMock;
            $group = new GroupMock;
            $extension = new ExtensionMock;
            $task = new Build(
                box: $this->box,
                group: $group,
                hub: $hub,
                extension: $extension,
                log: $this->log,
                config: [
                    "source" => "metadata1", // runtime layer
                    "environment" => $this->env
                ]);

            $counter = 0;
            $versions =
            $implication =
            $metas = [];
            $hub->version = function (array $source) use (&$counter, &$versions) {
                $versions[$counter] = $source;
                return $counter++;
            };

            $hub->metadata = function (array $source) use (&$counter, &$metas)  {
                $metas[$counter] = $source;
                return $counter++;
            };

            $hub->execute = function (Closure $callback) use (&$versions, &$metas) {
                while ($versions || $metas) {
                    foreach ($versions as $id => $versionRequest) {
                        unset($versions[$id]);

                        if ($versionRequest[0] == "metadata3")
                            $callback(new Versions($id, ["2.30.1", "2.0.0:offset", "1.0.0"]));

                        else $callback(new Versions($id, ["1.0.0"]));
                    }

                    foreach ($metas as $id => $metaRequest) {
                        unset($metas[$id]);

                        $callback(new Metadata($id, "", json_encode($metaRequest)));
                    }
                }
            };

            $this->solver->satisfiable = function () {return true;};
            $this->solver->path = function () {
                return [
                    "metadata1" => "1.0.0",
                    "metadata2" => "1.0.0",
                    "metadata3" => "2.0.0:offset",
                    "metadata4" => "1.0.0",
                    "metadata5" => "1.0.0",
                    "metadata6" => "1.0.0",
                    "metadata7" => "1.0.0"
                ];
            };
            $this->box->solver = function (...$args) use (&$implication){
                # implication, version, id
                $implication[] = $args["implication"];
                return $this->solver;
            };

            $this->box->builder = function (...$args) {
                # implication, version, id

                $mock = new BuilderMock(...$args);
                $mock->metadata = function ($source, $dir, $version) {
                    // parsed/normalized structure
                    if ($source == "metadata1")
                        $structure = ["sources" => [
                            "/dir1" => [
                                "metadata2",
                                "metadata4"
                            ],
                            "/dir2/dir3" => [
                                "metadata3"
                            ],
                        ]];

                    elseif ($source == "metadata3") {
                        $structure = ["sources" => [
                            "/dir4" => [ // nested
                                "metadata5",
                                "metadata2"
                            ]
                        ]];

                    } elseif ($source == "metadata5") {
                        $structure = ["sources" => [
                            "/dir5" => [ // nested
                                "metadata6"
                            ],
                            "/dir6" => [ // nested
                                "metadata7"
                            ]
                        ]];

                    } else
                        $structure = ["sources" => []];

                    return new ExternalMetadataMock([
                        "id" => $source,
                        "version" => $version,
                        "dir" => $dir,
                        "structure" => $structure,
                        "environment" => [
                            "php" => [
                                "modules" => [],
                                "version" => [[
                                    "major" => 8,
                                    "minor" => 1,
                                    "patch" => 0,
                                    "sign" => "", // default >=
                                    "release" => "",
                                    "build" => ""
                                ]]
                            ]
                        ],
                    ]);
                };

                return $mock;
            };

            $task->execute();

            // raw implication
            if ($implication != [[
                    "metadata2" => [
                        "source" => "metadata2",
                        "implication" => [
                            "1.0.0" => []
                        ],
                    ],
                    "metadata4" => [
                        "source" => "metadata4",
                        "implication" => [
                            "1.0.0" => []
                        ]
                    ],
                    "metadata3" => [
                        "source" => "metadata3",
                        "implication"=> [
                            "2.30.1"=> [
                                "metadata5" => [
                                    "source" => "metadata5",
                                    "implication" => [
                                        "1.0.0" => [
                                            "metadata6" => [
                                                "source"=> "metadata6",
                                                "implication" => [
                                                    "1.0.0" => []
                                                ]
                                            ],
                                            "metadata7"=> [
                                                "source" => "metadata7",
                                                "implication" => [
                                                    "1.0.0" => []
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                "metadata2"=> [
                                    "source" => "metadata2",
                                    "implication" => [
                                        "1.0.0" =>[]
                                    ]
                                ]
                            ],
                            "2.0.0:offset"=> [
                                "metadata5" => [
                                    "source" => "metadata5",
                                    "implication" => [
                                        "1.0.0" => [
                                            "metadata6" => [
                                                "source"=> "metadata6",
                                                "implication" => [
                                                    "1.0.0" => []
                                                ]
                                            ],
                                            "metadata7" => [
                                                "source" => "metadata7",
                                                "implication" => [
                                                    "1.0.0" => []
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                "metadata2" => [
                                    "source" => "metadata2",
                                    "implication" => [
                                        "1.0.0" => []
                                    ]
                                ]
                            ],
                            "1.0.0" => [
                                "metadata5" => [
                                    "source" => "metadata5",
                                    "implication" => [
                                        "1.0.0" => [
                                            "metadata6"=> [
                                                "source" => "metadata6",
                                                "implication"=> [
                                                    "1.0.0"=> []
                                                ]
                                            ],
                                            "metadata7" => [
                                                "source" => "metadata7",
                                                "implication" => [
                                                    "1.0.0"=> []
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                "metadata2" => [
                                    "source" => "metadata2",
                                    "implication" => [
                                        "1.0.0" => []
                                    ]
                                ]
                            ]
                        ]
                    ]]])
                $this->handleFailedTest();

            // solved implication
            if ($group->getImplication() != [
                    "metadata1"=> [
                        "source" => "metadata1",
                        "implication"=> [
                            "metadata2" => [
                                "source" => "metadata2",
                                "implication"=> []
                            ],
                            "metadata4" => [
                                "source" => "metadata4",
                                "implication" => []
                            ],
                            "metadata3" => [
                                "source" => "metadata3",
                                "implication" => [
                                    "metadata5" => [
                                        "source" => "metadata5",
                                        "implication" => [
                                            "metadata6" => [
                                                "source" => "metadata6",
                                                "implication" => []
                                            ],
                                            "metadata7" => [
                                                "source" => "metadata7",
                                                "implication" => []
                                            ]
                                        ]
                                    ],
                                    "metadata2" => [
                                        "source" => "metadata2",
                                        "implication" => []
                                    ]
                                ]
                            ]
                        ]
                    ]
                ])
                $this->handleFailedTest();

            if (array_diff([
                "metadata1",
                "metadata2",
                "metadata3",
                "metadata4",
                "metadata5",
                "metadata6",
                "metadata7"
            ], array_keys($group->externalMetas)))
                $this->handleFailedTest();

            // ids equal solver path
            // stacked dirs
            foreach ($group->externalMetas as $id => $metadata) {
                switch ($id) {
                    case "metadata1":
                        if ($metadata->getDir() != "" ||
                            $metadata->getVersion() != "1.0.0")
                                $this->handleFailedTest();

                        break;

                    case "metadata2":
                        // from metadata3 parent
                        if ($metadata->getDir() != "/dir2/dir3" ||
                            $metadata->getVersion() != "1.0.0")
                                $this->handleFailedTest();

                        break;

                    case "metadata3":
                        if ($metadata->getDir() != "/dir2/dir3" ||
                            $metadata->getVersion() != "2.0.0:offset")
                                $this->handleFailedTest();

                        break;

                    case "metadata4":
                        if ($metadata->getDir() != "/dir1" ||
                            $metadata->getVersion() != "1.0.0")
                                $this->handleFailedTest();

                        break;

                    case "metadata5":
                        // from metadata3 parent
                        if ($metadata->getDir() != "/dir2/dir3" ||
                            $metadata->getVersion() != "1.0.0")
                                $this->handleFailedTest();

                        break;

                    case "metadata6":
                        // from metadata5 to metadata3 parent
                        if ($metadata->getDir() != "/dir2/dir3" ||
                            $metadata->getVersion() != "1.0.0")
                                $this->handleFailedTest();

                        break;

                    case "metadata7":
                        // from metadata5 to metadata3 parent
                        if ($metadata->getDir() != "/dir2/dir3" ||
                            $metadata->getVersion() != "1.0.0")
                            $this->handleFailedTest();
                }
            }

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }

    public function testRecursiveMetadataImplication(): void
    {
        try {
            $hub = new HubMock;
            $group = new GroupMock;
            $extension = new ExtensionMock;
            $task = new Build(
                box: $this->box,
                group: $group,
                hub: $hub,
                extension: $extension,
                log: $this->log,
                config: [
                    "source" => false, // runtime layer
                    "environment" => $this->env
                ]);

            $group->internalRoot = new InternalMetadataMock([
                "structure" => [
                    "sources" => ["" => ["metadata1"]]
                ]
            ]);

            $counter = 0;
            $versions =
            $implication =
            $metas = [];
            $hub->version = function (array $source) use (&$counter, &$versions) {
                $versions[$counter] = $source;
                return $counter++;
            };

            $hub->metadata = function (array $source) use (&$counter, &$metas)  {
                $metas[$counter] = $source;
                return $counter++;
            };

            $hub->execute = function (Closure $callback) use (&$versions, &$metas) {
                while ($versions || $metas) {
                    foreach ($versions as $id => $versionRequest) {
                        unset($versions[$id]);

                        if ($versionRequest[0] == "metadata3")
                            $callback(new Versions($id, ["2.30.1", "2.0.0:offset", "1.0.0"]));

                        else $callback(new Versions($id, ["1.0.0"]));
                    }

                    foreach ($metas as $id => $metaRequest) {
                        unset($metas[$id]);

                        $callback(new Metadata($id, "", json_encode($metaRequest)));
                    }
                }
            };

            $this->solver->satisfiable = function () {return true;};
            $this->solver->path = function () {
                return [
                    "metadata1" => "1.0.0",
                    "metadata2" => "1.0.0",
                    "metadata3" => "2.0.0:offset",
                    "metadata4" => "1.0.0",
                    "metadata5" => "1.0.0",
                    "metadata6" => "1.0.0",
                    "metadata7" => "1.0.0"
                ];
            };
            $this->box->solver = function (...$args) use (&$implication){
                # implication, version, id
                $implication[] = $args["implication"];
                return $this->solver;
            };

            $this->box->builder = function (...$args) {
                # implication, version, id

                $mock = new BuilderMock(...$args);
                $mock->metadata = function ($source, $dir, $version) {
                    // parsed/normalized structure
                    if ($source == "metadata1")
                        $structure = ["sources" => [
                            "/dir1" => [
                                "metadata2",
                                "metadata4"
                            ],
                            "/dir2/dir3" => [
                                "metadata3"
                            ],
                        ]];

                    elseif ($source == "metadata3") {
                        $structure = ["sources" => [
                            "/dir4" => [ // nested
                                "metadata5",
                                "metadata2"
                            ]
                        ]];

                    } elseif ($source == "metadata5") {
                        $structure = ["sources" => [
                            "/dir5" => [ // nested
                                "metadata6"
                            ],
                            "/dir6" => [ // nested
                                "metadata7"
                            ]
                        ]];

                    } else
                        $structure = ["sources" => []];

                    return new ExternalMetadataMock([
                        "id" => $source,
                        "version" => $version,
                        "dir" => $dir,
                        "structure" => $structure,
                        "environment" => [
                            "php" => [
                                "modules" => [],
                                "version" => [[
                                    "major" => 8,
                                    "minor" => 1,
                                    "patch" => 0,
                                    "sign" => "", // default >=
                                    "release" => "",
                                    "build" => ""
                                ]]
                            ]
                        ],
                    ]);
                };

                return $mock;
            };

            $task->execute();

            // raw implication
            if ($implication != [[
                    "metadata2" => [
                        "source" => "metadata2",
                        "implication" => [
                            "1.0.0" => []
                        ],
                    ],
                    "metadata4" => [
                        "source" => "metadata4",
                        "implication" => [
                            "1.0.0" => []
                        ]
                    ],
                    "metadata3" => [
                        "source" => "metadata3",
                        "implication"=> [
                            "2.30.1"=> [
                                "metadata5" => [
                                    "source" => "metadata5",
                                    "implication" => [
                                        "1.0.0" => [
                                            "metadata6" => [
                                                "source"=> "metadata6",
                                                "implication" => [
                                                    "1.0.0" => []
                                                ]
                                            ],
                                            "metadata7"=> [
                                                "source" => "metadata7",
                                                "implication" => [
                                                    "1.0.0" => []
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                "metadata2"=> [
                                    "source" => "metadata2",
                                    "implication" => [
                                        "1.0.0" =>[]
                                    ]
                                ]
                            ],
                            "2.0.0:offset"=> [
                                "metadata5" => [
                                    "source" => "metadata5",
                                    "implication" => [
                                        "1.0.0" => [
                                            "metadata6" => [
                                                "source"=> "metadata6",
                                                "implication" => [
                                                    "1.0.0" => []
                                                ]
                                            ],
                                            "metadata7" => [
                                                "source" => "metadata7",
                                                "implication" => [
                                                    "1.0.0" => []
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                "metadata2" => [
                                    "source" => "metadata2",
                                    "implication" => [
                                        "1.0.0" => []
                                    ]
                                ]
                            ],
                            "1.0.0" => [
                                "metadata5" => [
                                    "source" => "metadata5",
                                    "implication" => [
                                        "1.0.0" => [
                                            "metadata6"=> [
                                                "source" => "metadata6",
                                                "implication"=> [
                                                    "1.0.0"=> []
                                                ]
                                            ],
                                            "metadata7" => [
                                                "source" => "metadata7",
                                                "implication" => [
                                                    "1.0.0"=> []
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                "metadata2" => [
                                    "source" => "metadata2",
                                    "implication" => [
                                        "1.0.0" => []
                                    ]
                                ]
                            ]
                        ]
                    ]]])
                $this->handleFailedTest();

            // solved implication
            if ($group->getImplication() != [
                    "metadata1"=> [
                        "source" => "metadata1",
                        "implication"=> [
                            "metadata2" => [
                                "source" => "metadata2",
                                "implication"=> []
                            ],
                            "metadata4" => [
                                "source" => "metadata4",
                                "implication" => []
                            ],
                            "metadata3" => [
                                "source" => "metadata3",
                                "implication" => [
                                    "metadata5" => [
                                        "source" => "metadata5",
                                        "implication" => [
                                            "metadata6" => [
                                                "source" => "metadata6",
                                                "implication" => []
                                            ],
                                            "metadata7" => [
                                                "source" => "metadata7",
                                                "implication" => []
                                            ]
                                        ]
                                    ],
                                    "metadata2" => [
                                        "source" => "metadata2",
                                        "implication" => []
                                    ]
                                ]
                            ]
                        ]
                    ]
                ])
                $this->handleFailedTest();

            if (array_diff([
                "metadata1",
                "metadata2",
                "metadata3",
                "metadata4",
                "metadata5",
                "metadata6",
                "metadata7"
            ], array_keys($group->externalMetas)))
                $this->handleFailedTest();

            // ids equal solver path
            // stacked dirs
            foreach ($group->externalMetas as $id => $metadata) {
                switch ($id) {
                    case "metadata1":
                        if ($metadata->getDir() != "" ||
                            $metadata->getVersion() != "1.0.0")
                            $this->handleFailedTest();

                        break;

                    case "metadata2":
                        // from metadata3 parent
                        if ($metadata->getDir() != "/dir2/dir3" ||
                            $metadata->getVersion() != "1.0.0")
                            $this->handleFailedTest();

                        break;

                    case "metadata3":
                        if ($metadata->getDir() != "/dir2/dir3" ||
                            $metadata->getVersion() != "2.0.0:offset")
                            $this->handleFailedTest();

                        break;

                    case "metadata4":
                        if ($metadata->getDir() != "/dir1" ||
                            $metadata->getVersion() != "1.0.0")
                            $this->handleFailedTest();

                        break;

                    case "metadata5":
                        // from metadata3 parent
                        if ($metadata->getDir() != "/dir2/dir3" ||
                            $metadata->getVersion() != "1.0.0")
                            $this->handleFailedTest();

                        break;

                    case "metadata6":
                        // from metadata5 to metadata3 parent
                        if ($metadata->getDir() != "/dir2/dir3" ||
                            $metadata->getVersion() != "1.0.0")
                            $this->handleFailedTest();

                        break;

                    case "metadata7":
                        // from metadata5 to metadata3 parent
                        if ($metadata->getDir() != "/dir2/dir3" ||
                            $metadata->getVersion() != "1.0.0")
                            $this->handleFailedTest();
                }
            }

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }

    public function testNestedMetadataImplication(): void
    {
        try {
            $hub = new HubMock;
            $group = new GroupMock;
            $extension = new ExtensionMock;
            $task = new Build(
                box: $this->box,
                group: $group,
                hub: $hub,
                extension: $extension,
                log: $this->log,
                config: [
                    "source" => false, // runtime layer
                    "environment" => $this->env
                ]);

            $group->internalRoot = new InternalMetadataMock([
                "id" => "metadata1",
                "version" => "1.0.0",
                "structure" => [

                    // nested deps
                    "sources" => [
                        "/dir1" => [
                            "metadata2",
                            "metadata4"
                        ],
                        "/dir2/dir3" => [
                            "metadata3"
                        ]
                    ]
                ],
                "environment" => [
                    "php" => [
                        "version" => [[
                            "major" => 8,
                            "minor" => 1,
                            "patch" => 0,
                            "sign" => "" // default >=
                        ]]
                    ]
                ]
            ]);

            $counter = 0;
            $versions =
            $implication =
            $metas = [];
            $hub->version = function (array $source) use (&$counter, &$versions) {
                $versions[$counter] = $source;
                return $counter++;
            };

            $hub->metadata = function (array $source) use (&$counter, &$metas)  {
                $metas[$counter] = $source;
                return $counter++;
            };

            $hub->execute = function (Closure $callback) use (&$versions, &$metas) {
                while ($versions || $metas) {
                    foreach ($versions as $id => $versionRequest) {
                        unset($versions[$id]);

                        if ($versionRequest[0] == "metadata3")
                            $callback(new Versions($id, ["2.30.1", "2.0.0:offset", "1.0.0"]));

                        else $callback(new Versions($id, ["1.0.0"]));
                    }

                    foreach ($metas as $id => $metaRequest) {
                        unset($metas[$id]);

                        $callback(new Metadata($id, "", json_encode($metaRequest)));
                    }
                }
            };

            $this->solver->satisfiable = function () {return true;};
            $this->solver->path = function () {
                return [
                    "metadata1" => "1.0.0",
                    "metadata2" => "1.0.0",
                    "metadata3" => "2.0.0:offset",
                    "metadata4" => "1.0.0",
                    "metadata5" => "1.0.0",
                    "metadata6" => "1.0.0",
                    "metadata7" => "1.0.0"
                ];
            };
            $this->box->solver = function (...$args) use (&$implication){
                # implication, version, id
                $implication[] = $args["implication"];
                return $this->solver;
            };

            $this->box->builder = function (...$args) {
                # implication, version, id

                $mock = new BuilderMock(...$args);
                $mock->metadata = function ($source, $dir, $version) {
                    // parsed/normalized structure
                    if ($source == "metadata1")
                        $structure = ["sources" => [
                            "/dir1" => [
                                "metadata2",
                                "metadata4"
                            ],
                            "/dir2/dir3" => [
                                "metadata3"
                            ],
                        ]];

                    elseif ($source == "metadata3") {
                        $structure = ["sources" => [
                            "/dir4" => [ // nested
                                "metadata5",
                                "metadata2"
                            ]
                        ]];

                    } elseif ($source == "metadata5") {
                        $structure = ["sources" => [
                            "/dir5" => [ // nested
                                "metadata6"
                            ],
                            "/dir6" => [ // nested
                                "metadata7"
                            ]
                        ]];

                    } else
                        $structure = ["sources" => []];

                    return new ExternalMetadataMock([
                        "id" => $source,
                        "version" => $version,
                        "dir" => $dir,
                        "structure" => $structure,
                        "environment" => [
                            "php" => [
                                "modules" => [],
                                "version" => [[
                                    "major" => 8,
                                    "minor" => 1,
                                    "patch" => 0,
                                    "sign" => "", // default >=
                                    "release" => "",
                                    "build" => ""
                                ]]
                            ]
                        ],
                    ]);
                };

                return $mock;
            };

            $task->execute();

            // raw implication
            if ($implication != [[
                    "metadata2" => [
                        "source" => "metadata2",
                        "implication" => [
                            "1.0.0" => []
                        ],
                    ],
                    "metadata4" => [
                        "source" => "metadata4",
                        "implication" => [
                            "1.0.0" => []
                        ]
                    ],
                    "metadata3" => [
                        "source" => "metadata3",
                        "implication"=> [
                            "2.30.1"=> [
                                "metadata5" => [
                                    "source" => "metadata5",
                                    "implication" => [
                                        "1.0.0" => [
                                            "metadata6" => [
                                                "source"=> "metadata6",
                                                "implication" => [
                                                    "1.0.0" => []
                                                ]
                                            ],
                                            "metadata7"=> [
                                                "source" => "metadata7",
                                                "implication" => [
                                                    "1.0.0" => []
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                "metadata2"=> [
                                    "source" => "metadata2",
                                    "implication" => [
                                        "1.0.0" =>[]
                                    ]
                                ]
                            ],
                            "2.0.0:offset"=> [
                                "metadata5" => [
                                    "source" => "metadata5",
                                    "implication" => [
                                        "1.0.0" => [
                                            "metadata6" => [
                                                "source"=> "metadata6",
                                                "implication" => [
                                                    "1.0.0" => []
                                                ]
                                            ],
                                            "metadata7" => [
                                                "source" => "metadata7",
                                                "implication" => [
                                                    "1.0.0" => []
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                "metadata2" => [
                                    "source" => "metadata2",
                                    "implication" => [
                                        "1.0.0" => []
                                    ]
                                ]
                            ],
                            "1.0.0" => [
                                "metadata5" => [
                                    "source" => "metadata5",
                                    "implication" => [
                                        "1.0.0" => [
                                            "metadata6"=> [
                                                "source" => "metadata6",
                                                "implication"=> [
                                                    "1.0.0"=> []
                                                ]
                                            ],
                                            "metadata7" => [
                                                "source" => "metadata7",
                                                "implication" => [
                                                    "1.0.0"=> []
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                "metadata2" => [
                                    "source" => "metadata2",
                                    "implication" => [
                                        "1.0.0" => []
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]])
                $this->handleFailedTest();

            // solved implication
            if ($group->getImplication() != [
                    "metadata2" => [
                        "source" => "metadata2",
                        "implication"=> []
                    ],
                    "metadata4" => [
                        "source" => "metadata4",
                        "implication" => []
                    ],
                    "metadata3" => [
                        "source" => "metadata3",
                        "implication" => [
                            "metadata5" => [
                                "source" => "metadata5",
                                "implication" => [
                                    "metadata6" => [
                                        "source" => "metadata6",
                                        "implication" => []
                                    ],
                                    "metadata7" => [
                                        "source" => "metadata7",
                                        "implication" => []
                                    ]
                                ]
                            ],
                            "metadata2" => [
                                "source" => "metadata2",
                                "implication" => []
                            ]
                        ]
                    ]
                ])
                $this->handleFailedTest();

            if (array_diff([
                "metadata2",
                "metadata3",
                "metadata4",
                "metadata5",
                "metadata6",
                "metadata7"
            ], array_keys($group->externalMetas)))
                $this->handleFailedTest();

            // ids equal solver path
            // stacked dirs
            foreach ($group->externalMetas as $id => $metadata) {
                switch ($id) {
                    case "metadata2":
                        // from metadata3 parent
                        if ($metadata->getDir() != "/dir2/dir3" ||
                            $metadata->getVersion() != "1.0.0")
                            $this->handleFailedTest();

                        break;

                    case "metadata3":
                        if ($metadata->getDir() != "/dir2/dir3" ||
                            $metadata->getVersion() != "2.0.0:offset")
                            $this->handleFailedTest();

                        break;

                    case "metadata4":
                        if ($metadata->getDir() != "/dir1" ||
                            $metadata->getVersion() != "1.0.0")
                            $this->handleFailedTest();

                        break;

                    case "metadata5":
                        // from metadata3 parent
                        if ($metadata->getDir() != "/dir2/dir3" ||
                            $metadata->getVersion() != "1.0.0")
                            $this->handleFailedTest();

                        break;

                    case "metadata6":
                        // from metadata5 to metadata3 parent
                        if ($metadata->getDir() != "/dir2/dir3" ||
                            $metadata->getVersion() != "1.0.0")
                            $this->handleFailedTest();

                        break;

                    case "metadata7":
                        // from metadata5 to metadata3 parent
                        if ($metadata->getDir() != "/dir2/dir3" ||
                            $metadata->getVersion() != "1.0.0")
                            $this->handleFailedTest();
                }
            }

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }
}