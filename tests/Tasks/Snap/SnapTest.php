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

namespace Valvoid\Fusion\Tests\Tasks\Snap;

use Exception;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Tasks\Snap\Snap;
use Valvoid\Fusion\Tests\Tasks\Snap\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Snap\Mocks\BusMock;
use Valvoid\Fusion\Tests\Tasks\Snap\Mocks\ExternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Snap\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Snap\Mocks\InternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Snap\Mocks\LogMock;
use Valvoid\Fusion\Tests\Test;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;
/**
 * Integration test case for the snap task.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class SnapTest extends Test
{
    protected string|array $coverage = Snap::class;

    public function __construct()
    {
        $box = new BoxMock;
        $group = new GroupMock;
        $box->group = $group;
        $group->hasDownloadable = false;
        $box->bus = new BusMock;
        $box->log = new LogMock;

        try {
            $this->delete(__DIR__ . "/Mocks/package");
            $group->implication = [
                "metadata1" => [
                    "implication" => [
                        "metadata2" => [
                            "implication" => []
                        ],
                        "metadata3" => [
                            "implication" => []
                        ]
                    ]
                ]
            ];
            $group->externalMetas["metadata1"] = new ExternalMetadataMock(
                ExternalCategory::REDUNDANT,[
                "id" => "metadata1",
                "name" => "metadata1",
                "description" => "metadata1",
                "version" => "1.0.0",
                "source" => [
                    "api" => "",
                    "path" => "",
                    "prefix" => "",
                    "reference" => ""
                ],
                "dir" => "", // test recursive root
                "dependencies" => [

                    // external === production only
                    // production metadata has deps
                    // fusion.json
                    "production" => [
                        "metadata2",
                        "metadata3"
                    ]
                ]
            ]);

            $group->externalRoot = $group->externalMetas["metadata1"];
            $group->externalMetas["metadata2"] = new ExternalMetadataMock(
                ExternalCategory::REDUNDANT,[
                "id" => "metadata2",
                "name" => "metadata2",
                "description" => "metadata2",
                "version" => "3.2.1",
                "source" => [
                    "api" => "",
                    "path" => "",
                    "prefix" => "",
                    "reference" => "offset" // version offset
                ],
                "dir" => "metadata2"
            ],["object" => [
                "version" => "3.2.1" // pseudo version for offset
            ]]);
            $group->externalMetas["metadata3"] = new ExternalMetadataMock(
                ExternalCategory::REDUNDANT,[
                "id" => "metadata3",
                "name" => "metadata3",
                "description" => "metadata3",
                "version" => "1.2.3",
                "source" => [
                    "api" => "",
                    "path" => "",
                    "prefix" => "",
                    "reference" => "1.2.3" // version
                ],
                "dir" => "metadata3"
            ]);
            $this->testRedundantCacheRefresh();

            // clear
            $group = new GroupMock;
            $box->group = $group;
            $group->hasDownloadable = true;
            $group->implication = [
                "metadata1" => [
                    "implication" => [
                        "metadata2" => [
                            "implication" => [
                                "metadata3" => [
                                    "implication" => []
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $group->internalMetas["metadata1"] = new InternalMetadataMock(
                InternalCategory::RECYCLABLE,[
                "id" => "metadata1",
                "name" => "metadata1",
                "description" => "metadata1",
                "source" => "", // project root
                "dir" => "", // project root
                "version" => "1.0.0",
                "dependencies" => [

                    // production metadata has deps
                    // fusion.json
                    "production" => [
                        "metadata2"
                    ],

                    // optional empty dev metadata
                    // fusion.dev.php
                    "development" => [],

                    // no optional local fusion metadata file
                    // fusion.local.php
                    "local" => null
                ],
                "structure" => [
                    "cache" => "/cache"
                ]
            ]);

            $group->internalRoot = $group->internalMetas["metadata1"];
            $group->externalMetas["metadata2"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE,[
                "id" => "metadata2",
                "name" => "metadata2",
                "description" => "metadata2",
                "version" => "1.0.0",
                "dir" => "metadata2",
                "source" => [
                    "api" => "",
                    "path" => "",
                    "prefix" => "",
                    "reference" => "6.7.8" // version
                ],

            ]);
            $group->externalMetas["metadata3"] = new ExternalMetadataMock(
                ExternalCategory::REDUNDANT,[
                "id" => "metadata3",
                "name" => "metadata3",
                "description" => "metadata3",
                "version" => "1.0.0",
                "dir" => "metadata3",
                "source" => [
                    "api" => "",
                    "path" => "",
                    "prefix" => "",
                    "reference" => "offset" // version offset
                ],
            ],["object" => [
                "version" => "5.4.3" // pseudo version for offset
            ]]);
            $this->testDownloadableCacheUpdate();

        } catch (Exception) {
            $this->handleFailedTest();
        }

        $box::unsetInstance();
    }

    /**
     * @throws Error
     */
    public function testRedundantCacheRefresh(): void
    {
        $snap = new Snap([]);
        $snap->execute();

        $snapshot = file_get_contents(__DIR__ . "/Mocks/package/cache/snapshot.json");

        if ($snapshot) {
            $snapshot = json_decode($snapshot, true);

            // no recursive root
            // offset
            if (["metadata2" => "3.2.1:offset", "metadata3" => "1.2.3"] === $snapshot)
                return;
        }

        $this->handleFailedTest();
    }

    /**
     * @throws Error
     */
    public function testDownloadableCacheUpdate(): void
    {
        $prefix = __DIR__ . "/Mocks/package/cache/packages/metadata1/cache";
        $snap = new Snap([]);
        $snap->execute();

        // no local metadata
        // fusion.local.php is null
        if (!file_exists("$prefix/snapshot.local.json")) {
            $snapshot = file_get_contents("$prefix/snapshot.json");
            $devSnapshot = file_get_contents("$prefix/snapshot.dev.json");

            if ($snapshot && $devSnapshot) {
                $snapshot = json_decode($snapshot, true);
                $devSnapshot = json_decode($devSnapshot, true);

                // keep order but actually no matter
                if (["metadata3" => "5.4.3:offset", "metadata2" => "6.7.8"] === $snapshot &&

                    // existing fusion.dev.php contains no deps
                    $devSnapshot === [] )
                    return;
            }
        }

       $this->handleFailedTest();
    }

    /**
     * @param string $file
     * @return void
     */
    public function delete(string $file): void
    {
        if (is_dir($file)) {
            foreach (scandir($file, SCANDIR_SORT_NONE) as $filename)
                if ($filename != "." && $filename != "..")
                    $this->delete("$file/$filename");

            rmdir($file);

        } elseif (is_file($file))
            unlink($file);
    }
}