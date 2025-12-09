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

namespace Valvoid\Fusion\Tests\Tasks\Extend;

use Exception;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;
use Valvoid\Fusion\Tasks\Extend\Extend;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\DirectoryMock;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\ExternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\FileMock;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\InternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\LogMock;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Tests\Test;

class ExtendTest extends Test
{
    protected string|array $coverage = Extend::class;
    private BoxMock $box;
    private LogMock $log;

    public function __construct()
    {
        $this->box = new BoxMock;
        $this->log = new LogMock;

        $this->testCurrentStateRefresh();
        $this->testNewState();
        $this->testNewStateWithRecycledRoot();

        $this->box::unsetInstance();
    }

    public function testCurrentStateRefresh(): void
    {
        try {
            $group = new GroupMock;
            $directory = new DirectoryMock;
            $fileWrapper = new FileMock;
            $task = new Extend(
                $this->box,
                $group,
                $this->log,
                $directory,
                $fileWrapper,
                []);

            $group->implication = ["i1/i1" => [
                "implication" => []
            ]];

            $group->internalMetas["i0"] = new InternalMetadataMock(
                InternalCategory::RECYCLABLE, [
                "id" => "i0",
                "source" => "/s0",
                "dir" => "/d0",
                "structure" => [
                    "cache" => "/state",
                    "mappings" => [
                        // 1:1 mapping
                        "/##" => ":i1/i1/ex00",
                        "/####" => ":i1/i1/ex00"
                    ],
                    "extendables" => [],
                    "sources" => [
                        "/deps" => []
                    ]
                ]
            ]);

            $group->hasDownloadable = false;
            $group->internalRoot = $group->internalMetas["i0"];
            $group->internalMetas["i1/i1"] = new InternalMetadataMock(
                InternalCategory::RECYCLABLE, [
                "source" => "/s0/deps/s1",
                "structure" => [
                    "cache" => "/state",
                    "mappings" => [],
                    "extendables" => [
                        "/ex00",
                    ],
                    "sources" => []
                ]
            ]);

            $create =
            $put = [];

            // i0, i1 cache dirs must exist
            $directory->create = function (string $dir) use (&$create) {
                $create[] = $dir;
            };

            // extensions.php
            $fileWrapper->put = function (string $file, string $content) use (&$put) {
                $put[] = [
                    "file" => $file,
                    "content" => $content
                ];

                // pass
                return 1;
            };

            $task->execute();

            // prepare cache dirs
            if ($create != ["/s0/state", "/s0/deps/s1/state"])
                $this->handleFailedTest();

            if ($put != [[
                    "file" => "/s0/state/extensions.php",
                    "content" => "<?php return [\n];"
                ], [
                    "file" => "/s0/deps/s1/state/extensions.php",
                    "content" => "<?php return [" .

                        // test order
                        "\n\t\"/ex00\" => [" .
                        "\n\t\t1 => dirname(__DIR__, 4) . \"/d0/####\"," . // 1:1 mapping
                        "\n\t]," .
                        "\n];"

                ]]) $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }

    public function testNewState(): void
    {
        try {
            $group = new GroupMock;
            $directory = new DirectoryMock;
            $fileWrapper = new FileMock;
            $task = new Extend(
                $this->box,
                $group,
                $this->log,
                $directory,
                $fileWrapper,
                []);

            $group->internalMetas = [
                "i0" => new InternalMetadataMock(
                    InternalCategory::OBSOLETE, [
                    "id" => "i0",
                    "dir" => "", // relative to root dir
                    "structure" => [
                        "cache" => "/state",
                        "mappings" => [],
                        "extendables" => [],
                        "sources" => [
                            "/deps" => []
                        ]
                    ]
                ])
            ];

            $group->hasDownloadable = true;
            $group->internalRoot = $group->internalMetas["i0"];
            $group->implication = [
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
            ];

            $group->externalMetas["i0"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE, [
                "id" => "i0",
                "dir" => "", // relative to root dir
                "structure" => [
                    "cache" => "/state",
                    "mappings" => [
                        "/###i0" => ":i1/ex",
                        "/###i00" => ":i1/ex0"
                    ],
                    "extendables" => [],
                    "sources" => [
                        "/deps" => [
                            "i1",
                            "i2"
                        ]
                    ]
                ]
            ]);

            $group->externalRoot = $group->externalMetas["i0"];
            $group->externalMetas["i1"] = new ExternalMetadataMock(
                ExternalCategory::REDUNDANT, [
                "id" => "i1",
                "dir" => "/deps/i1",
                "structure" => [
                    "cache" => "/state",
                    "mappings" => [],
                    "sources" => [],
                    "extendables" => [
                        "/ex0"
                    ]
                ]
            ]);

            $group->externalMetas["i2"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE, [
                "id" => "i2",
                "dir" => "/deps/i2",
                "structure" => [
                    "cache" => "/state",
                    "mappings" => [
                        "/###i2" => ":i1/ex",
                        "/###i22" => ":i1/ex0"
                    ],
                    "extendables" => [],
                    "sources" => [
                        "/deps" => ["i1"]
                    ]
                ]
            ]);

            $create =
            $put = [];

            // cached individual packages
            $directory->packages = function () {
                return "/p";
            };

            // deps dirs
            // i0, i1, i2 cache dirs must exist
            $directory->create = function (string $dir) use (&$create) {
                $create[] = $dir;
            };

            // extensions.php
            $fileWrapper->put = function (string $file, string $content) use (&$put) {
                $put[] = [
                    "file" => $file,
                    "content" => $content
                ];

                // pass
                return 1;
            };

            $task->execute();

            if ($create != ["/p/i0/state", "/p/i1/state", "/p/i2/state"])
                $this->handleFailedTest();

            if ($put != [[
                    "file" => "/p/i0/state/extensions.php",
                    "content" => "<?php return [\n];"
                ], [
                    "file" => "/p/i1/state/extensions.php",
                    "content" => "<?php return [" .

                        // test order
                        // mapping
                        "\n\t\"/ex0\" => [" .
                        "\n\t\t2 => dirname(__DIR__, 3) . \"/deps/i2/###i22\"," .
                        "\n\t\t3 => dirname(__DIR__, 3) . \"/###i00\"," .
                        "\n\t]," .
                        "\n];"

                ],[
                    "file" => "/p/i2/state/extensions.php",
                    "content" => "<?php return [\n];"
                ]]) $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }

    public function testNewStateWithRecycledRoot(): void
    {
        try {
            $group = new GroupMock;
            $directory = new DirectoryMock;
            $fileWrapper = new FileMock;
            $task = new Extend(
                $this->box,
                $group,
                $this->log,
                $directory,
                $fileWrapper,
                []);

            $group->internalMetas = [
                "i0" => new InternalMetadataMock(
                    InternalCategory::RECYCLABLE, [
                    "id" => "i0",
                    "dir" => "", // relative to root dir
                    "structure" => [
                        "cache" => "/state",
                        "mappings" => [
                            "/###i0" => ":i1/ex",
                            "/###i00" => ":i1/ex0"
                        ],
                        "extendables" => [],
                        "sources" => [
                            "/deps" => [
                                "i1",
                                "i2"
                            ]
                        ]
                    ]
                ])
            ];

            $group->hasDownloadable = true;
            $group->internalRoot = $group->internalMetas["i0"];
            $group->implication = [
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
            ];

            $group->externalMetas["i0"] = new ExternalMetadataMock(
                ExternalCategory::REDUNDANT, [
                "id" => "i0",
                "dir" => "", // relative to root dir
                "structure" => [
                    "cache" => "/state",
                    "mappings" => [
                        "/###i0" => ":i1/ex",
                        "/###i00" => ":i1/ex0"
                    ],
                    "extendables" => [],
                    "sources" => [
                        "/deps" => [
                            "i1",
                            "i2"
                        ]
                    ]
                ]
            ]);

            $group->externalRoot = $group->externalMetas["i0"];
            $group->externalMetas["i1"] = new ExternalMetadataMock(
                ExternalCategory::REDUNDANT, [
                "id" => "i1",
                "dir" => "/deps/i1",
                "structure" => [
                    "cache" => "/state",
                    "mappings" => [],
                    "sources" => [],
                    "extendables" => [
                        "/ex0"
                    ],
                ]
            ]);

            $group->externalMetas["i2"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE, [
                "id" => "i2",
                "dir" => "/deps/i2",
                "structure" => [
                    "cache" => "/state",
                    "mappings" => [
                        "/###i2" => ":i1/ex",
                        "/###i22" => ":i1/ex0"
                    ],
                    "extendables" => [],
                    "sources" => [
                        "/deps" => ["i1"]
                    ]
                ]
            ]);

            $create =
            $put = [];

            // cached individual packages
            $directory->packages = function () {
                return "/p";
            };

            // deps dirs
            // i0, i1, i2 cache dirs must exist
            $directory->create = function (string $dir) use (&$create) {
                $create[] = $dir;
            };

            // extensions.php
            $fileWrapper->put = function (string $file, string $content) use (&$put) {
                $put[] = [
                    "file" => $file,
                    "content" => $content
                ];

                // pass
                return 1;
            };

            $task->execute();

            if ($create != ["/p/i0/state", "/p/i1/state", "/p/i2/state"])
                $this->handleFailedTest();

            if ($put != [[
                    "file" => "/p/i0/state/extensions.php",
                    "content" => "<?php return [\n];"
                ], [
                    "file" => "/p/i1/state/extensions.php",
                    "content" => "<?php return [" .

                        // test order
                        "\n\t\"/ex0\" => [" .
                        "\n\t\t2 => dirname(__DIR__, 3) . \"/deps/i2/###i22\"," .
                        "\n\t\t3 => dirname(__DIR__, 3) . \"/###i00\"," .
                        "\n\t]," .
                        "\n];"

                ],[
                    "file" => "/p/i2/state/extensions.php",
                    "content" => "<?php return [\n];"
                ]]) $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }
}