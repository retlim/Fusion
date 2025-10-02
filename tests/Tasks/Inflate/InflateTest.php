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

namespace Valvoid\Fusion\Tests\Tasks\Inflate;

use Exception;
use Valvoid\Fusion\Metadata\Internal\Category;
use Valvoid\Fusion\Tasks\Inflate\Inflate;
use Valvoid\Fusion\Tests\Tasks\Inflate\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Inflate\Mocks\DirectoryMock;
use Valvoid\Fusion\Tests\Tasks\Inflate\Mocks\DirMock;
use Valvoid\Fusion\Tests\Tasks\Inflate\Mocks\ExternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Inflate\Mocks\FileMock;
use Valvoid\Fusion\Tests\Tasks\Inflate\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Inflate\Mocks\InternalMetadataMock;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Tests\Tasks\Inflate\Mocks\LogMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Integration test case for the inflate task.
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class InflateTest extends Test
{
    protected string|array $coverage = Inflate::class;
    private BoxMock $box;

    public function __construct()
    {
        $this->box = new BoxMock;

        $this->testRefresh();
        $this->testDownloadable();

        $this->box::unsetInstance();
    }

    public function testRefresh(): void
    {
        try {
            $directory = new DirectoryMock;
            $file = new FileMock;
            $dir = new DirMock;
            $group = new GroupMock;
            $inflate = new Inflate(
                box: $this->box,
                group: $group,
                directory: $directory,
                log: new LogMock,
                file: $file,
                dir: $dir,
                config: []
            );

            $group->hasDownloadable = false;
            $group->internalMetas["i0"] = new InternalMetadataMock(
                InternalCategory::RECYCLABLE, [
                "source" => "/s0",
                "structure" => [
                    "cache" => "/c0",
                    "namespaces" => [],
                ]
            ]);

            $group->internalMetas["i1"] = new InternalMetadataMock(
                InternalCategory::RECYCLABLE, [
                "source" =>"/s1",
                "structure" => [
                    "cache" => "/c1",
                    "namespaces" => []
                ]
            ]);

            $group->internalMetas["i2"] = new InternalMetadataMock(
                InternalCategory::RECYCLABLE, [
                "source" => "/s2",
                "structure" => [
                    "cache" => "/c2",
                    "namespaces" => []
                ]
            ]);

            // do not loop
            $group->internalMetas["i3"] = new InternalMetadataMock(
                InternalCategory::OBSOLETE, []);

            $filenames =
            $isDir =
            $get =
            $create =
            $delete = [];

            $dir->filenames = function (string $dir) use (&$filenames) {
                $filenames[] = $dir;

                if ($dir == "/s0")
                    return ["d0", "f0.php"];

                // read dir
                if ($dir == "/s0/d0")
                    return ["f1.php", "f2"]; // .php extension

                if ($dir == "/s1")
                    return ["f3.php"];

                if ($dir == "/s2")
                    return ["f4.php", "f5.php"];

                return [];
            };

            $dir->is = function (string $dir) use (&$isDir) {
                $isDir[] = $dir;
                return $dir == "/s0/d0";
            };

            $directory->create = function (string $dir) use (&$create) {
                $create[] = $dir;
            };

            $directory->delete = function (string $dir) use (&$delete) {
                $delete[] = $dir;
            };

            $file->put = function (string $file, mixed $data) use (&$put) {
                $put[] = [
                    "file" => $file,
                    "data" => $data
                ];

                return 1;
            };

            $file->get = function (string $file) use (&$get) {
                $get[] = $file;

                if ($file == "/s0/f0.php")
                    return "<?php\n" .
                        "namespace I0;\n".
                        "class Any {}";

                if ($file == "/s0/d0/f1.php")
                    return "<?php\n" .
                        "namespace I0 {\n" .
                            "function whatever() {}\n" .
                        "}";

                if ($file == "/s1/f3.php")
                    return "<?php\n" .
                        "namespace I1\Any;\n". // prefix
                        "interface Any {}";

                if ($file == "/s2/f4.php")
                    return "<?php\n" .
                        "namespace I2;\n".
                        "trait Any {}";

                if ($file == "/s2/f5.php")
                    return "<?php\n" .
                        "namespace I;\n". // prefix but no segment
                        "class Any {}";

                return "#";
            };

            $inflate->execute();

            if ($get != [
                    "/s0/d0/f1.php",
                    "/s0/f0.php",
                    "/s1/f3.php",
                    "/s2/f4.php",
                    "/s2/f5.php"] ||
                $create != [
                    "/s0/c0/loadable",
                    "/s0/c0/loadable",
                    "/s1/c1/loadable",
                    "/s2/c2/loadable"] ||
                $filenames != [
                    "/s0",
                    "/s0/d0",
                    "/s1",
                    "/s2"] ||
                $isDir != [
                    "/s0/d0",
                    "/s0/d0/f1.php",
                    "/s0/d0/f2",
                    "/s0/f0.php",
                    "/s1/f3.php",
                    "/s2/f4.php",
                    "/s2/f5.php"] ||
                $delete != [
                    "/s0/c0/loadable",
                    "/s1/c1/loadable",
                    "/s2/c2/loadable"] ||
                $put != [
                    [
                        "file" => "/s0/c0/loadable/lazy.php",
                        "data" => "<?php\n" .
                            "// Auto-generated by Fusion package manager.\n" .
                            "// Do not modify.\n" .
                            "return [\n" .
                            "\t'I0\Any' => '/f0.php',\n" .
                            "];"
                    ],
                    [
                        "file" => "/s0/c0/loadable/asap.php",
                        "data" => "<?php\n" .
                            "// Auto-generated by Fusion package manager.\n" .
                            "// Do not modify.\n" .
                            "return [\n" .
                            "\t'/d0/f1.php',\n" .
                            "];"
                    ],
                    [
                        "file" => "/s1/c1/loadable/lazy.php",
                        "data" => "<?php\n" .
                            "// Auto-generated by Fusion package manager.\n" .
                            "// Do not modify.\n" .
                            "return [\n" .
                            "\t'I1\Any\Any' => '/f3.php',\n" .
                            "];"
                    ],
                    [
                        "file" => "/s2/c2/loadable/lazy.php",
                        "data" => "<?php\n" .
                            "// Auto-generated by Fusion package manager.\n" .
                            "// Do not modify.\n" .
                            "return [\n" .
                            "\t'I2\Any' => '/f4.php',\n" .
                            "];"
                    ]

                ]) $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }

    public function testDownloadable(): void
    {
        try {
            $directory = new DirectoryMock;
            $file = new FileMock;
            $dir = new DirMock;
            $group = new GroupMock;
            $inflate = new Inflate(
                box: $this->box,
                group: $group,
                directory: $directory,
                log: new LogMock,
                file: $file,
                dir: $dir,
                config: []
            );

            $group->hasDownloadable = true;
            $group->internalMetas = ["i0" => new InternalMetadataMock(
                Category::OBSOLETE, [])];

            $group->externalMetas["i0"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE,[
                "structure" => [
                    "cache" => "/cache",
                    "namespaces" => []
                ]
            ]);

            $group->externalMetas["i1"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE,[
                "structure" => [
                    "namespaces" => [],
                    "cache" => "/cache"
                ]
            ]);

            $group->externalMetas["i2"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE,[
                "structure" => [
                    "cache" => "/cache",
                    "namespaces" => []
                ]
            ]);

            $filenames =
            $isDir =
            $get =
            $create =
            $delete = [];
            $directory->packages = function () {return "/#";};
            $directory->delete = function (string $dir) use (&$delete) {
                $delete[] = $dir;
            };

            $dir->filenames = function (string $dir) use (&$filenames) {
                $filenames[] = $dir;

                if ($dir == "/#/i0")
                    return ["d0", "f0.php"];

                if ($dir == "/#/i0/d0")
                    return ["f1"];

                if ($dir == "/#/i1")
                    return ["f2.php"];

                if ($dir == "/#/i2")
                    return ["f3.php"];

                return [];
            };

            $dir->is = function (string $dir) use (&$isDir) {
                $isDir[] = $dir;
                return $dir == "/#/i0/d0";
            };

            $file->get = function (string $file) use (&$get) {
                $get[] = $file;

                if ($file == "/#/i0/f0.php")
                    return "<?php\n" .
                        "namespace I0;\n".
                        "final class Any {}";

                if ($file == "/#/i1/f2.php")
                    return "<?php\n" .
                        "namespace I1;\n".
                        "abstract class Any {}";

                if ($file == "/#/i2/f3.php")
                    return "<?php\n" .
                        "namespace I2;\n".
                        "enum Any {}";

                return "#";
            };

            $directory->create = function (string $dir) use (&$create) {
                $create[] = $dir;
            };

            $file->put = function (string $file, mixed $data) use (&$put) {
                $put[] = [
                    "file" => $file,
                    "data" => $data
                ];

                return 1;
            };

            $inflate->execute();

            if ($get != [
                    "/#/i0/f0.php",
                    "/#/i1/f2.php",
                    "/#/i2/f3.php"] ||
                $create != [
                    "/#/i0/cache/loadable",
                    "/#/i1/cache/loadable",
                    "/#/i2/cache/loadable"] ||
                $filenames != [
                    "/#/i0",
                    "/#/i0/d0",
                    "/#/i1",
                    "/#/i2"] ||
                $isDir != [
                    "/#/i0/d0",
                    "/#/i0/d0/f1",
                    "/#/i0/f0.php",
                    "/#/i1/f2.php",
                    "/#/i2/f3.php"] ||
                $delete != [
                    "/#/i0/cache/loadable",
                    "/#/i1/cache/loadable",
                    "/#/i2/cache/loadable"] ||
                $put != [[
                        "file" => "/#/i0/cache/loadable/lazy.php",
                        "data" => "<?php\n" .
                            "// Auto-generated by Fusion package manager.\n" .
                            "// Do not modify.\n" .
                            "return [\n" .
                            "\t'I0\Any' => '/f0.php',\n" .
                            "];"
                    ],
                    [
                        "file" => "/#/i1/cache/loadable/lazy.php",
                        "data" => "<?php\n" .
                            "// Auto-generated by Fusion package manager.\n" .
                            "// Do not modify.\n" .
                            "return [\n" .
                            "\t'I1\Any' => '/f2.php',\n" .
                            "];"
                    ],
                    [
                        "file" => "/#/i2/cache/loadable/lazy.php",
                        "data" => "<?php\n" .
                            "// Auto-generated by Fusion package manager.\n" .
                            "// Do not modify.\n" .
                            "return [\n" .
                            "\t'I2\Any' => '/f3.php',\n" .
                            "];"
                    ]

                ]) $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }
}