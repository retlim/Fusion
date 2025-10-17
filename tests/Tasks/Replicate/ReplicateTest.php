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
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Valvoid\Fusion\Tests\Tasks\Replicate;

use Closure;
use Exception;
use Valvoid\Fusion\Hub\Responses\Cache\Metadata;
use Valvoid\Fusion\Hub\Responses\Cache\Snapshot;
use Valvoid\Fusion\Hub\Responses\Cache\Versions;
use Valvoid\Fusion\Tasks\Replicate\Replicate;
use Valvoid\Fusion\Tests\Tasks\Replicate\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Replicate\Mocks\BuilderMock;
use Valvoid\Fusion\Tests\Tasks\Replicate\Mocks\DirectoryMock;
use Valvoid\Fusion\Tests\Tasks\Replicate\Mocks\ExtensionMock;
use Valvoid\Fusion\Tests\Tasks\Replicate\Mocks\ExternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Replicate\Mocks\FileMock;
use Valvoid\Fusion\Tests\Tasks\Replicate\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Replicate\Mocks\HubMock;
use Valvoid\Fusion\Tests\Tasks\Replicate\Mocks\InternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Replicate\Mocks\LogMock;
use Valvoid\Fusion\Tests\Test;

class ReplicateTest extends Test
{
    protected string|array $coverage = Replicate::class;
    private BoxMock $box;
    private array $environment = [
        "php" => [
            "version" => [
                "major" => PHP_MAJOR_VERSION,
                "minor" => PHP_MINOR_VERSION,
                "patch" => PHP_RELEASE_VERSION,

                // placeholder
                "release" => "",
                "build" => ""
            ]
        ]
    ];

    public function __construct()
    {
        $this->box = new BoxMock;

        $this->testSourceSnapshot();
        $this->testCachedSnapshotFiles();

        $this->box::unsetInstance();
    }

    public function testSourceSnapshot(): void
    {
        try {
            $group = new GroupMock;
            $hub = new HubMock;
            $directory = new DirectoryMock;
            $extension = new ExtensionMock;
            $file = new FileMock;
            $replicate = new Replicate(
                box: $this->box,
                group: $group,
                hub: $hub,
                directory: $directory,
                extension: $extension,
                log: new LogMock,
                file: $file,
                config: [
                    "environment" => $this->environment,
                    "source" => "i0",
                ]);

            $this->box->builder = function (...$args) {
                # implication, version, id
                $mock = new BuilderMock(...$args);
                $mock->metadata = function ($source, $dir, $version) {
                    // parsed/normalized structure
                    if ($source == "i0")
                        $structure = [
                            "cache" => "/c0",
                            "sources" => [
                                "/deps" => ["i1"]
                            ]
                        ];

                    else $structure = ["sources" => []];

                    return new ExternalMetadataMock([
                        "id" => $source,
                        "source" => [$source],
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

            $counter = 0;
            $versions =
            $snapshots =
            $metas = [];
            $hub->version = function (array $source) use (&$counter, &$versions) {
                $versions[$counter] = $source;
                return $counter++;
            };

            $hub->metadata = function (array $source) use (&$counter, &$metas)  {
                $metas[$counter] = $source;
                return $counter++;
            };

            $hub->snapshot = function (array $source) use (&$counter, &$snapshots)  {
                $snapshots[$counter] = $source;
                return $counter++;
            };

            $hub->execute = function (Closure $callback) use (&$versions, &$metas, &$snapshots) {
                while ($versions || $metas || $snapshots) {
                    foreach ($versions as $id => $versionRequest) {
                        unset($versions[$id]);

                        if ($versionRequest[0] == "i0")
                            $callback(new Versions($id, ["2.30.1", "2.0.0:offset", "1.0.0"]));

                        else $callback(new Versions($id, ["1.0.0"]));
                    }

                    foreach ($metas as $id => $metaRequest) {
                        unset($metas[$id]);

                        $callback(new Metadata($id, "", json_encode($metaRequest)));
                    }

                    foreach ($snapshots as $id => $snapshotRequest) {
                        unset($snapshots[$id]);

                        $callback(new Snapshot($id, "", json_encode(["i1" => "1.2.3"])));
                    }
                }
            };

            $replicate->execute();

            $metas = $group->getExternalMetas();

            if (array_keys($metas) != ["i0", "i1"] ||
                $metas["i0"]?->getDir() != "" ||
                $metas["i1"]?->getDir() != "/deps" ||
                $group->getImplication() != [
                    "i0" => [
                        "source" => "i0",
                        "implication" => [
                            "i1" => [
                                "source" => "i1",
                                "implication" => []
                            ]
                        ]
                    ]

                ]) $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }

    public function testCachedSnapshotFiles(): void
    {
        try {
            $group = new GroupMock;
            $hub = new HubMock;
            $directory = new DirectoryMock;
            $extension = new ExtensionMock;
            $file = new FileMock;
            $replicate = new Replicate(
                box: $this->box,
                group: $group,
                hub: $hub,
                directory: $directory,
                extension: $extension,
                log: new LogMock,
                file: $file,
                config: [
                    "environment" => $this->environment,
                    "source" => false,
                ]);

            $group->internalMetas["i0"] = new InternalMetadataMock([
                "structure" => [
                    "cache" => "/state",
                    "sources" => [
                        // actually source like adapter/id/pattern
                        "/deps" => ["i1", "i2", "i3"]
                    ]
                ]
            ]);

            $group->internalRoot = $group->internalMetas["i0"];

            $counter = 0;
            $metas =
            $get =
            $exists = [];
            $directory->cache = function () {return "/#";};
            $file->exists = function (string $file) use (&$exists) {
                $exists[] = $file;
                return true;
            };

            $file->get = function (string $file) use (&$get) {
                $get[] = $file;

                if ($file == "/#/snapshot.json")
                    return "{\"i1\": \"1.0.0\"}";

                if ($file == "/#/snapshot.dev.json")
                    return "{\"i2\": \"2.0.0\",\"i4\": \"0.5.0-beta\"}";

                if ($file == "/#/snapshot.local.json")
                    return "{\"i3\": \"1.2.3\"}";

                return "#";
            };

            $hub->metadata = function (array $source) use (&$counter, &$metas)  {
                $metas[$counter] = $source;
                return $counter++;
            };

            $hub->execute = function (Closure $callback) use (&$metas) {
                while ($metas) {
                    foreach ($metas as $id => $metaRequest) {
                        unset($metas[$id]);

                        $callback(new Metadata($id, "", json_encode($metaRequest)));
                    }
                }
            };
            $this->box->builder = function (...$args) {
                # implication, version, id
                $mock = new BuilderMock(...$args);
                $mock->metadata = function ($source, $dir, $version) {
                    // parsed/normalized structure
                    if ($source == "i1")
                        $structure = ["sources" => [
                            "/d1" => [
                                "i4",
                            ]
                        ]];

                    else $structure = ["sources" => []];

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

            $replicate->execute();

            $metas = $group->getExternalMetas();

            if ($get != [
                    "/#/snapshot.json",
                    "/#/snapshot.dev.json",
                    "/#/snapshot.local.json"] ||
                $exists != [
                    "/#/snapshot.json",
                    "/#/snapshot.dev.json",
                    "/#/snapshot.local.json"] ||
                array_keys($metas) != ["i1", "i2", "i3", "i4"] ||
                $group->getImplication() != [
                    "i1" => [
                        "source" => "i1",
                        "implication" => [
                            "i4" => [
                                "source" => "i4",
                                "implication" => []
                            ]
                        ]
                    ],
                    "i2" => [
                        "source" => "i2",
                        "implication" => []
                    ],
                    "i3" => [
                        "source" => "i3",
                        "implication" => []
                    ]
                ]) $this->handleFailedTest();


            foreach ($metas as $metadata)
                if ($metadata->getDir() != "/deps")
                    $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }
}