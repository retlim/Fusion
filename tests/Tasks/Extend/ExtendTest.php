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

namespace Valvoid\Fusion\Tests\Tasks\Extend;

use Exception;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;
use Valvoid\Fusion\Tasks\Extend\Extend;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\DirectoryMock;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\DirMock;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\ExternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\FileMock;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\InternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\LogMock;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Tests\Test;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
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

        $this->box::unsetInstance();
    }

    public function testCurrentStateRefresh(): void
    {
        try {
            $group = new GroupMock;
            $directory = new DirectoryMock;
            $dirWrapper = new DirMock;
            $fileWrapper = new FileMock;
            $task = new Extend(
                $this->box,
                $group,
                $this->log,
                $directory,
                $fileWrapper,
                $dirWrapper,
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
                        "/###" => ":i1/i1/ex"
                    ],
                    "extensions" => [],
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
                    "extensions" => [
                        "/ex"
                    ],
                    "sources" => []
                ]
            ]);

            $create =
            $put =
            $filenames =
            $delete = [];

            // i0, i1 cache dirs must exist
            $directory->create = function (string $dir) use (&$create) {
                $create[] = $dir;
            };

            // i1 has obsolete i2 extension
            $directory->delete = function (string $file) use (&$delete) {
                $delete[] = $file;
            };

            // checks custom ext dirs inside dep
            $dirWrapper->is = function (string $dir) {
                return $dir == "/s0/deps/s1/ex" ||
                    $dir == "/s0/deps/s1/ex/i0" ||
                    $dir == "/s0/deps/s1/ex/i1" ||
                    $dir == "/s0/deps/s1/ex/i1/i1" ||
                    $dir == "/s0/deps/s1/ex/i2";
            };

            // filter custom extensions
            // inside dependency package
            $dirWrapper->filenames = function (string $dir) use (&$filenames) {
                $filenames[] = $dir;

                if ($dir == "/s0/deps/s1/ex")
                    return ["i0", "i1", "i2"];

                if ($dir == "/s0/deps/s1/ex/i1")
                    return ["i1"];

                return [];
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

            // delete obsolete extension
            if ($delete != ["/s0/deps/s1/ex/i2"])
                $this->handleFailedTest();

            // implication
            if ($filenames != ["/s0/deps/s1/ex", "/s0/deps/s1/ex/i1"])
                $this->handleFailedTest();

            if ($put != [[
                    "file" => "/s0/state/extensions.php",
                    "content" => "<?php\n" .
                        "// Auto-generated by Fusion package manager. \n" .
                        "// Do not modify.\n" .
                        "return [" .
                        "\n];"
                ], [
                    "file" => "/s0/deps/s1/state/extensions.php",
                    "content" => "<?php\n" .
                        "// Auto-generated by Fusion package manager. \n" .
                        "// Do not modify.\n" .
                        "return [" .

                        // test order
                        "\n\t\"/ex\" => [" .
                        "\n\t\t0 => \"i1/i1\"," .
                        "\n\t\t1 => \"/d0/###\"," .
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
            $dirWrapper = new DirMock;
            $fileWrapper = new FileMock;
            $task = new Extend(
                $this->box,
                $group,
                $this->log,
                $directory,
                $fileWrapper,
                $dirWrapper,
                []);

            $group->internalMetas = [
                "i0" => new InternalMetadataMock(
                    InternalCategory::OBSOLETE, [
                    "id" => "i0",
                    "dir" => "", // relative to root dir
                    "structure" => [
                        "cache" => "/state",
                        "mappings" => [],
                        "extensions" => [],
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
                        "/###i0" => ":i1/ex"
                    ],
                    "extensions" => [],
                    "sources" => [
                        "/deps" => [
                            "i1",
                            "i2"
                        ]
                    ]
                ]
            ]);

            $group->externalMetas["i1"] = new ExternalMetadataMock(
                ExternalCategory::REDUNDANT, [
                "id" => "i1",
                "dir" => "/deps/i1",
                "structure" => [
                    "cache" => "/state",
                    "mappings" => [],
                    "sources" => [],
                    "extensions" => [
                        "/ex"
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
                        "/###i2" => ":i1/ex"
                    ],
                    "extensions" => [],
                    "sources" => [
                        "/deps" => ["i1"]
                    ]
                ]
            ]);

            $create =
            $put =
            $filenames =
            $rename =
            $clear =
            $delete = [];

            // cached individual packages
            $directory->packages = function () {
                return "/p";
            };

            // deps dirs
            // i0, i1, i2 cache dirs must exist
            $directory->create = function (string $dir) use (&$create) {
                $create[] = $dir;
            };

            $dirWrapper->is = function (string $dir) {

                // downloaded with deps
                return $dir == "/p/i0/deps/i1/ex/i0" ||
                    $dir == "/p/i2/deps/i1/ex/i2" ||

                    // renamed into individual package
                    $dir == "/p/i1/ex" ||
                    $dir == "/p/i1/ex/i0" ||
                    $dir == "/p/i1/ex/i2";
            };

            $directory->clear = function (string $dir, string $path) use (&$clear) {
                $clear[] = [
                    "dir" => $dir,
                    "path" => $path
                ];
            };

            $directory->rename = function (string $from, string $to) use (&$rename) {
                $rename[] = [
                    "from" => $from,
                    "to" => $to
                ];
            };

            // prepare to dirs
            $directory->delete = function (string $file) use (&$delete) {
                $delete[] = $file;
            };

            // filter custom extensions
            // inside dependency package
            $dirWrapper->filenames = function (string $dir) use (&$filenames) {
                $filenames[] = $dir;

                if ($dir == "/p/i1/ex")
                    return ["i0", "i2"];

                return [];
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

            if ($create != ["/p/i1/ex/i0", "/p/i1/ex/i2",
                    "/p/i0/state", "/p/i1/state", "/p/i2/state"])
                $this->handleFailedTest();

            if ($delete != ["/p/i1/ex/i0", "/p/i1/ex/i2"])
                $this->handleFailedTest();

            if ($rename != [[
                    "from" => "/p/i0/deps/i1/ex/i0",
                    "to" => "/p/i1/ex/i0"
                ],[
                    "from" => "/p/i2/deps/i1/ex/i2",
                    "to" => "/p/i1/ex/i2"
                ]]) $this->handleFailedTest();

            if ($clear != [[
                    "dir" => "/p/i0/deps",
                    "path" => "/i1/ex/i0"
                ],[
                    "dir" => "/p/i2/deps",
                    "path" => "/i1/ex/i2"
                ]]) $this->handleFailedTest();

            // implication
            if ($filenames != ["/p/i1/ex"])
                $this->handleFailedTest();

            if ($put != [[
                    "file" => "/p/i0/state/extensions.php",
                    "content" => "<?php\n" .
                        "// Auto-generated by Fusion package manager. \n" .
                        "// Do not modify.\n" .
                        "return [" .
                        "\n];"
                ], [
                    "file" => "/p/i1/state/extensions.php",
                    "content" => "<?php\n" .
                        "// Auto-generated by Fusion package manager. \n" .
                        "// Do not modify.\n" .
                        "return [" .

                        // test order
                        "\n\t\"/ex\" => [" .
                        "\n\t\t2 => \"/deps/i2/###i2\"," .
                        "\n\t\t3 => \"/###i0\"," .
                        "\n\t\t4 => \"/###i0\"," .
                        "\n\t]," .
                        "\n];"

                ],[
                    "file" => "/p/i2/state/extensions.php",
                    "content" => "<?php\n" .
                        "// Auto-generated by Fusion package manager. \n" .
                        "// Do not modify.\n" .
                        "return [" .
                        "\n];"
                ]]) $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }
}