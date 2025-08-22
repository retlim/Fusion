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

namespace Valvoid\Fusion\Tests\Tasks\Register;

use Exception;
use Valvoid\Fusion\Tasks\Register\Register;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Tests\Tasks\Register\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Register\Mocks\BusMock;
use Valvoid\Fusion\Tests\Tasks\Register\Mocks\ExternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Register\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Register\Mocks\InternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Register\Mocks\LogMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Integration test case for the register task.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class RegisterTest extends Test
{
    protected string|array $coverage = Register::class;

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
            $task = new Register([]);
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

            $group->internalRoot = $group->internalMetas["metadata1"];
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
            $this->testRefreshAutoloader();

            $group = new GroupMock;
            $box->group = $group;
            $group->hasDownloadable = true;
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
            $group->internalRoot = $group->internalMetas["metadata1"];
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
            $task = new Register([]);

            $task->execute();
            $this->testNewStateAutoloader();
            $box::unsetInstance();

        } catch (Exception $exception) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;
            echo "\n " . $exception->getMessage();

            $box::unsetInstance();

            $this->result = false;
        }
    }

    public function testRefreshAutoloader(): void
    {
        $autoloader = __DIR__ . "/Mocks/package/cache/Autoloader.php";

        if (is_file($autoloader) && filemtime($autoloader) >= $this->time)
            return;

        $this->handleFailedTest();
    }

    public function testNewStateAutoloader(): void
    {
        $autoloader = __DIR__ . "/Mocks/package/cache/packages/metadata1/cache/Autoloader.php";

        if (is_file($autoloader) && filemtime($autoloader) >= $this->time)
            return;

        $this->handleFailedTest();
    }
}