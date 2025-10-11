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

namespace Valvoid\Fusion\Tests\Tasks\Snap;

use Exception;
use Valvoid\Fusion\Tasks\Snap\Snap;
use Valvoid\Fusion\Tests\Tasks\Snap\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Snap\Mocks\DirectoryMock;
use Valvoid\Fusion\Tests\Tasks\Snap\Mocks\ExternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Snap\Mocks\FileMock;
use Valvoid\Fusion\Tests\Tasks\Snap\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Snap\Mocks\InternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Snap\Mocks\LogMock;
use Valvoid\Fusion\Tests\Test;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class SnapTest extends Test
{
    protected string|array $coverage = Snap::class;
    private BoxMock $box;
    public function __construct()
    {
        $this->box = new BoxMock;

        // has external root package
        // only production metas
        $this->testCurrentRecursiveState();
        $this->testNewRecursiveState();

        // same root === no external root
        // could be development env
        // local, dev, prod metas === snaps
        $this->testDependencyState();

        $this->box::unsetInstance();
    }

    public function testCurrentRecursiveState(): void
    {
        try {
            $directory = new DirectoryMock;
            $file = new FileMock;
            $group = new GroupMock;
            $snap = new Snap(
                box: $this->box,
                group: $group,
                log: new LogMock,
                directory: $directory,
                file: $file,
                config: []
            );

            $group->hasDownloadable = false;
            $group->implication = [
                "i0" => [
                    "implication" => [
                        "i1" => ["implication" => []],
                        "i2" => ["implication" => []]
                    ]
                ]
            ];

            $group->externalMetas["i0"] = new ExternalMetadataMock(
                ExternalCategory::REDUNDANT,[
                "id" => "i0",
                "source" => ["reference" => ""],
                "dependencies" => [
                    "production" => ["i1", "i2"]
                ]
            ]);

            $group->externalRoot = $group->externalMetas["i0"];
            $group->externalMetas["i1"] = new ExternalMetadataMock(
                ExternalCategory::REDUNDANT,[
                    "source" => ["reference" => "offset"],
                ],[
                    "object" => ["version" => "3.2.1"]
                ]);

            $group->externalMetas["i2"] = new ExternalMetadataMock(
                ExternalCategory::REDUNDANT,[
                "source" => ["reference" => "1.2.3"]
            ]);

            $create =
            $put = [];

            $directory->cache = function () {
                return "/state";
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

            $snap->execute();

            if ($create != ["/state"] ||
                $put != [[
                    "file" => "/state/snapshot.json",
                    "data" => "{\n" .
                        "    \"i1\": \"3.2.1:offset\",\n" .
                        "    \"i2\": \"1.2.3\"\n" .
                    "}"]])
                $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }

    public function testNewRecursiveState(): void
    {
        try {
            $directory = new DirectoryMock;
            $file = new FileMock;
            $group = new GroupMock;
            $snap = new Snap(
                box: $this->box,
                group: $group,
                log: new LogMock,
                directory: $directory,
                file: $file,
                config: []
            );

            $group->hasDownloadable = true;
            $group->implication = [
                "i0" => [
                    "implication" => [
                        "i1" => ["implication" => []],
                        "i2" => ["implication" => []]
                    ]
                ]
            ];

            $group->externalMetas["i0"] = new ExternalMetadataMock(
                ExternalCategory::REDUNDANT,[
                "id" => "i0",
                "source" => ["reference" => ""],
                "structure" => [
                    "cache" => "/state"
                ],
                "dependencies" => [
                    "production" => ["i1", "i2"]
                ]
            ]);

            $group->externalRoot = $group->externalMetas["i0"];
            $group->externalMetas["i1"] = new ExternalMetadataMock(
                ExternalCategory::REDUNDANT,[
                "source" => ["reference" => "offset"],
            ],[
                "object" => ["version" => "3.2.1"]
            ]);

            $group->externalMetas["i2"] = new ExternalMetadataMock(
                ExternalCategory::REDUNDANT,[
                "source" => ["reference" => "1.2.3"]
            ]);

            $create =
            $put = [];

            $directory->packages = function () {
                return "/tmp/packages";
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

            $snap->execute();

            if ($create != ["/tmp/packages/i0/state"] ||
                $put != [[
                    "file" => "/tmp/packages/i0/state/snapshot.json",
                    "data" => "{\n" .
                        "    \"i1\": \"3.2.1:offset\",\n" .
                        "    \"i2\": \"1.2.3\"\n" .
                        "}"]])
                $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }

    public function testDependencyState(): void
    {
        try {
            $directory = new DirectoryMock;
            $file = new FileMock;
            $group = new GroupMock;
            $snap = new Snap(
                box: $this->box,
                group: $group,
                log: new LogMock,
                directory: $directory,
                file: $file,
                config: []
            );

            $group->hasDownloadable = true;
            $group->implication = [
                "i1" => ["implication" => []],
                "i2" => ["implication" => []]
            ];

            $group->internalMetas["i0"] = new InternalMetadataMock(
                InternalCategory::RECYCLABLE,[
                "id" => "i0",
                "source" => ["reference" => ""],
                "structure" => [
                    "cache" => "/state"
                ],
                "dependencies" => [
                    "production" => ["i1"],
                    "development" => ["i2"],
                    "local" => null
                ]
            ]);

            $group->internalRoot = $group->internalMetas["i0"];
            $group->externalMetas["i1"] = new ExternalMetadataMock(
                ExternalCategory::REDUNDANT,[
                "source" => ["reference" => "offset"],
            ],[
                "object" => ["version" => "3.2.1"]
            ]);

            $group->externalMetas["i2"] = new ExternalMetadataMock(
                ExternalCategory::REDUNDANT,[
                "source" => ["reference" => "1.2.3"]
            ]);

            $create =
            $delete =
            $put = [];

            $directory->packages = function () {
                return "/tmp/packages";
            };

            $directory->create = function (string $dir) use (&$create) {
                $create[] = $dir;
            };

            $directory->delete = function (string $file) use (&$delete) {
                $delete[] = $file;
            };

            $file->put = function (string $file, mixed $data) use (&$put) {
                $put[] = [
                    "file" => $file,
                    "data" => $data
                ];

                return 1;
            };

            $snap->execute();

            if ($create != ["/tmp/packages/i0/state"] ||
                $delete != ["/tmp/packages/i0/state/snapshot.local.json"] ||
                $put != [[
                    "file" => "/tmp/packages/i0/state/snapshot.json",
                    "data" => "{\n" .
                        "    \"i1\": \"3.2.1:offset\"\n" .
                        "}"],
                    [
                        "file" => "/tmp/packages/i0/state/snapshot.dev.json",
                        "data" => "{\n" .
                            "    \"i2\": \"1.2.3\"\n" .
                            "}"]])
                $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }
}