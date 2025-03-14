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
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tasks\Replicate\Replicate;
use Valvoid\Fusion\Tests\Tasks\Replicate\Mocks\ContainerMock;
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
        try {
            $this->time = time();
            $containerMock = new ContainerMock;
            $task = new Replicate([
                "source" => false,
                "environment" => $this->environment
            ]);

            MetadataMock::addRootMetadata();

            $task->execute();
            $this->testCachedSnapshotFiles();
            $containerMock->destroy();

        } catch (Exception $exception) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;
            echo "\n " . $exception->getMessage();


                $containerMock->destroy();

            $this->result = false;
        }
    }

    public function testCachedSnapshotFiles(): void
    {
        $metas = Group::getExternalMetas();

        if (isset($metas["test/local"]) &&
            isset($metas["test/development"]) &&
            isset($metas["test/production"]))
            return;

        echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

        $this->result = false;
    }
}