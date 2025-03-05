<?php
/**
 * Fusion. A package manager for PHP-based projects.
 * Copyright Valvoid
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

use Exception;
use Valvoid\Fusion\Tasks\Build\Build;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tests\Tasks\Build\Mocks\ContainerMock;
use Valvoid\Fusion\Tests\Tasks\Build\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Build\Mocks\HubMock;
use Valvoid\Fusion\Tests\Tasks\Build\Mocks\LogMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Test case for the build task.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class BuildTest extends Test
{
    protected string|array $coverage = Build::class;

    private HubMock $hubMock;
    private ContainerMock $containerMock;

    public function __construct()
    {
        try {
            $log = new LogMock;
            $this->hubMock = new HubMock;
            $group = new GroupMock;
            $this->containerMock = new ContainerMock;

            $this->testExternalRootSourceImplication();

            $this->containerMock->destroy();
            $group->destroy();
            $this->hubMock->destroy();
            $log->destroy();

        } catch (Exception $exception) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;
            echo "\n " . $exception->getMessage();

            if (isset($this->containerMock))
                $this->containerMock->destroy();

            if (isset($group))
                $group->destroy();

            if (isset($this->hubMock))
                $this->hubMock->destroy();

            if (isset($log))
                $log->destroy();
        }
    }

    public function testExternalRootSourceImplication(): void
    {
        // recursive runtime layer source arg
        $this->hubMock->setUpExternalRootSourceImplication();
        $this->containerMock->setUpExternalRootSourceImplication();

        $task = new Build([
            "source" => "metadata1", // runtime layer
            "environment" => [
                "php" => [
                    "version" => [
                        "major" => 8,
                        "minor" => 1,
                        "patch" => 0
                    ]
                ]
            ]]);

        $task->execute();

        // invalid raw version implication passed to solver
        if ($this->containerMock->logic->implication != [
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
            ]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " . __LINE__;

            $this->result = false;
        }

        // invalid implication
        if (Group::getImplication() != [
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
            ]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " . __LINE__;

            $this->result = false;
        }

        $metas = Group::getExternalMetas();
        $line = "";

        // missing identifier
        if (array_diff(array_keys($metas), [
            "metadata1",
            "metadata2",
            "metadata3",
            "metadata4",
            "metadata5",
            "metadata6",
            "metadata7"
        ])) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " . __LINE__;

            $this->result = false;
        }

        // ids equal solver path
        // stacked dirs
        foreach (Group::getExternalMetas() as $id => $metadata) {
            switch ($id) {
                case "metadata1":
                    if ($metadata->getDir() == "") {
                        if ($metadata->getVersion() == "1.0.0")
                            continue 2;

                        else $line = __LINE__;

                    } else $line = __LINE__;

                case "metadata2":
                    // from metadata3 parent
                    if ($metadata->getDir() == "/dir2/dir3") {
                        if ($metadata->getVersion() == "1.0.0")
                            continue 2;

                        else $line = __LINE__;

                    } else $line = __LINE__;

                case "metadata3":
                    if ($metadata->getDir() == "/dir2/dir3") {
                        if ($metadata->getVersion() == "2.0.0:offset")
                            continue 2;

                        else $line = __LINE__;

                    } else $line = __LINE__;

                case "metadata4":
                    if ($metadata->getDir() == "/dir1") {
                        if ($metadata->getVersion() == "1.0.0")
                            continue 2;

                        else $line = __LINE__;

                    } else $line = __LINE__;

                case "metadata5":
                    // from metadata3 parent
                    if ($metadata->getDir() == "/dir2/dir3") {
                        if ($metadata->getVersion() == "1.0.0")
                            continue 2;

                        else $line = __LINE__;

                    } else $line = __LINE__;

                case "metadata6":
                    // from metadata5 to metadata3 parent
                    if ($metadata->getDir() == "/dir2/dir3") {
                        if ($metadata->getVersion() == "1.0.0")
                            continue 2;

                        else $line = __LINE__;

                    } else $line = __LINE__;

                case "metadata7":
                    // from metadata5 to metadata3 parent
                    if ($metadata->getDir() == "/dir2/dir3") {
                        if ($metadata->getVersion() == "1.0.0")
                            continue 2;

                        else $line = __LINE__;

                    } else $line = __LINE__;
            }

            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | $line";

            $this->result = false;
        }
    }
}