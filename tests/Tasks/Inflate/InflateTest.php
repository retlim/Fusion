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

namespace Valvoid\Fusion\Tests\Tasks\Inflate;

use Exception;
use Valvoid\Fusion\Tasks\Inflate\Inflate;
use Valvoid\Fusion\Tests\Tasks\Inflate\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Inflate\Mocks\BusMock;
use Valvoid\Fusion\Tests\Tasks\Inflate\Mocks\ExternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Inflate\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Inflate\Mocks\InternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Inflate\Mocks\LogMock;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Tests\Test;

/**
 * Integration test case for the inflate task.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class InflateTest extends Test
{
    protected string|array $coverage = Inflate::class;
    private string $cache = __DIR__ . "/Mocks/package/cache/packages";
    private string $dependencies = __DIR__ . "/Mocks/package/dependencies";
    private int $time;

    public function __construct()
    {
        $box = new BoxMock;
        $group = new GroupMock;
        $box->group = $group;
        $group->hasDownloadable = false;
        $box->bus = new BusMock;
        $box->log = new LogMock;

        try {
            $this->time = time();
            $task = new Inflate([]);
            $group->implication = [
                "metadata2" => [ // no external root
                    "implication" => []
                ], "metadata3" => [
                    "implication" => []
                ]];

            $group->internalMetas["metadata1"] = new InternalMetadataMock([
                "id" => "metadata1",
                "name" => "metadata1",
                "description" => "metadata1",
                "source" => __DIR__ . "/Mocks/package",
                "dir" => "", // relative to root dir
                "version" => "1.0.0",
                "structure" => [
                    "cache" => "/cache",
                    "namespaces" => [],
                ]
            ]);

            $group->internalMetas["metadata2"] = new InternalMetadataMock([
                "id" => "metadata2",
                "name" => "metadata2",
                "description" => "metadata2",
                "source" => __DIR__ . "/Mocks/package/dependencies/metadata2",
                "dir" => "/dependencies/metadata2", // relative to root dir
                "version" => "1.0.0",
                "structure" => [
                    "cache" => "/cache",
                    "namespaces" => []
                ]
            ]);

            $group->internalMetas["metadata3"] = new InternalMetadataMock([
                "id" => "metadata3",
                "name" => "metadata3",
                "description" => "metadata3",
                "source" => __DIR__ . "/Mocks/package/dependencies/metadata3",
                "dir" => "/dependencies/metadata3", // relative to root dir
                "version" => "1.0.0",
                "structure" => [
                    "cache" => "/cache",
                    "namespaces" => []
                ]
            ]);

            $task->execute();
            $this->testRefreshClassAndFunction();
            $this->testRefreshInterface();
            $this->testRefreshTrait();

            $group = new GroupMock;
            $box->group = $group;
            $group->hasDownloadable = true;
            $group->internalMetas = ["metadata1" => new InternalMetadataMock([
                "id" => "metadata1",
                "name" => "metadata1",
                "description" => "metadata1",
                "source" => __DIR__ . "/Mocks/package",
                "dir" => "", // relative to root dir
                "version" => "1.0.0",
                "structure" => [
                    "namespaces" => [],
                    "cache" => "/cache",
                    "extensions" => [],
                    "sources" => [
                        "/dependencies" => []
                    ]
                ]
            ])];

            $group->externalMetas["metadata1"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE,[
                "id" => "metadata1",
                "name" => "metadata1",
                "description" => "metadata1",
                "source" => "/package",
                "dir" => "", // relative to root dir
                "version" => "1.0.0",
                "structure" => [
                    "cache" => "/cache",
                    "namespaces" => [],
                    "extensions" => [],
                    "sources" => [
                        "/dependencies" => [
                            "metadata2",
                            "metadata3"
                        ]
                    ]
                ]
            ]);

            $group->externalMetas["metadata2"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE,[
                "id" => "metadata2",
                "name" => "metadata2",
                "description" => "metadata2",
                "source" => "/package/dependencies/metadata2",
                "dir" => "/dependencies/metadata2",
                "version" => "1.0.0",
                "structure" => [
                    "namespaces" => [],
                    "cache" => "/cache",
                    "extensions" => [
                        "/extensions"
                    ],
                    "sources" => []
                ]
            ]);

            $group->externalMetas["metadata3"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE,[
                "id" => "metadata3",
                "name" => "metadata3",
                "description" => "metadata3",
                "source" => "whatever/metadata3",
                "dir" => "/dependencies/metadata3",
                "version" => "1.0.0",
                "structure" => [
                    "cache" => "/cache",
                    "extensions" => [],
                    "namespaces" => [],
                    "sources" => [
                        "/dependencies" => ["metadata2"]
                    ]
                ]
            ]);

            $task = new Inflate([]);
            $group->implication = [
                "metadata1" => [
                    "implication" => [
                        "metadata2" => [
                            "implication" => []
                        ],
                        "metadata3" => [
                            "implication" => [
                                "metadata2" => [
                                    "implication" => []
                                ]
                            ]
                        ],
                    ]
                ]
            ];

            $task->execute();
            $this->testNewStateFinalClass();
            $this->testNewStateAbstractClass();
            $this->testNewStateEnum();
            $box::unsetInstance();

        } catch (Exception) {
            $this->handleFailedTest();
        }

        $box::unsetInstance();
    }

    public function testRefreshClassAndFunction(): void
    {
        $loadable = __DIR__ . "/Mocks/package/cache/loadable";
        $asap = "$loadable/asap.php";
        $lazy = "$loadable/lazy.php";

        if (is_file($asap) && is_file($lazy) &&
            filemtime($asap) >= $this->time && filemtime($lazy) >= $this->time) {
            $asap = include "$loadable/asap.php";
            $lazy = include "$loadable/lazy.php";

            if ($lazy == ['Metadata1\Metadata1' => '/Metadata1.php'] &&
                $asap == ["/metadata_1.php"])
                return;
        }

        $this->handleFailedTest();
    }

    public function testRefreshInterface(): void
    {
        $loadable = "$this->dependencies/metadata2/cache/loadable";
        $lazy = "$loadable/lazy.php";

        if (is_file($lazy) && filemtime($lazy) >= $this->time) {
            $lazy = include "$loadable/lazy.php";

            if ($lazy == ['Metadata2\Whatever\Metadata2' => '/Metadata2.php'])
                return;
        }

        $this->handleFailedTest();
    }

    public function testRefreshTrait(): void
    {
        $loadable = "$this->dependencies/metadata3/cache/loadable";
        $lazy = "$loadable/lazy.php";

        if (is_file($lazy) && filemtime($lazy) >= $this->time) {
            $lazy = include "$loadable/lazy.php";

            if ($lazy == ['Metadata3\Metadata3' => '/Metadata3.php'])
                return;
        }

        $this->handleFailedTest();
    }

    public function testNewStateFinalClass(): void
    {
        $loadable = "$this->cache/metadata1/cache/loadable";
        $lazy = "$loadable/lazy.php";

        if (is_file($lazy) && filemtime($lazy) >= $this->time) {
            $lazy = include "$loadable/lazy.php";

            if ($lazy == ['Metadata1\Final\Metadata1' => '/Metadata1.php'])
                return;
        }

        $this->handleFailedTest();
    }

    public function testNewStateAbstractClass(): void
    {
        $loadable = "$this->cache/metadata2/cache/loadable";
        $lazy = "$loadable/lazy.php";

        if (is_file($lazy) && filemtime($lazy) >= $this->time) {
            $lazy = include "$loadable/lazy.php";

            if ($lazy == ['Metadata2\Metadata2' => '/Metadata2.php'])
                return;
        }

        $this->handleFailedTest();
    }

    public function testNewStateEnum(): void
    {
        $loadable = "$this->cache/metadata3/cache/loadable";
        $lazy = "$loadable/lazy.php";

        if (is_file($lazy) && filemtime($lazy) >= $this->time) {
            $lazy = include "$loadable/lazy.php";

            if ($lazy == ['Metadata3\Enum\Metadata3' => '/Metadata3.php'])
                return;
        }

        $this->handleFailedTest();
    }
}