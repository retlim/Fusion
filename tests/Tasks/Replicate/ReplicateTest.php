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

namespace Valvoid\Fusion\Tests\Tasks\Replicate;

use Exception;
use Valvoid\Fusion\Tasks\Replicate\Replicate;
use Valvoid\Fusion\Tests\Tasks\Replicate\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Replicate\Mocks\BusMock;
use Valvoid\Fusion\Tests\Tasks\Replicate\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Replicate\Mocks\InternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Replicate\Mocks\LogMock;
use Valvoid\Fusion\Tests\Tasks\Replicate\Mocks\MetadataMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Integration test case for the replicate task.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ReplicateTest extends Test
{
    protected string|array $coverage = Replicate::class;

    private int $time;
    protected GroupMock $group;

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
        $box = new BoxMock;
        $this->group = new GroupMock;
        $box->group = $this->group;
        $box->bus = new BusMock;
        $box->log = new LogMock;

        try {
            $this->time = time();
            $task = new Replicate([
                "source" => false,
                "environment" => $this->environment
            ]);

            $this->group->internalMetas["metadata1"] = new InternalMetadataMock([
                "id" => "metadata1",
                "name" => "metadata1",
                "description" => "metadata1",
                "source" => __DIR__ . "/Mocks/package",
                "dir" => "", // relative to root dir
                "version" => "1.0.0",
                "structure" => [
                    "cache" => "/cache",
                    "namespaces" => [],
                    "sources" => [
                        "/deps" => [
                            "a/test/production/1.0.0",
                            "a/test/local/1.0.0",
                            "a/test/development/1.0.0"
                        ]
                    ]
                ]
            ]);

            $this->group->internalRoot = $this->group->internalMetas["metadata1"];

            $task->execute();
            $this->testCachedSnapshotFiles();
            $box::unsetInstance();

        } catch (Exception $exception) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;
            echo "\n " . $exception->getMessage();

            $box::unsetInstance();

            $this->result = false;
        }
    }

    public function testCachedSnapshotFiles(): void
    {
        $metas = $this->group->getExternalMetas();

        if (isset($metas["test/local"]) &&
            isset($metas["test/development"]) &&
            isset($metas["test/production"]))
            return;

        $this->handleFailedTest();
    }
}