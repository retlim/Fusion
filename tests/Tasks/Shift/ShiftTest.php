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

namespace Valvoid\Fusion\Tests\Tasks\Shift;

use Exception;
use ReflectionClass;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Tasks\Shift\Shift;
use Valvoid\Fusion\Tests\Tasks\Shift\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Shift\Mocks\BusMock;
use Valvoid\Fusion\Tests\Tasks\Shift\Mocks\ExternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Shift\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Shift\Mocks\InternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Shift\Mocks\LogMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Integration test case for the shift task.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ShiftTest extends Test
{
    protected string|array $coverage = Shift::class;

    private string $cache = __DIR__ . '/cache';
    private GroupMock $group;
    public function __construct()
    {
        $box = new BoxMock;
        $this->group = new GroupMock;
        $box->group = $this->group;
        $box->bus = new BusMock;
        $box->log = new LogMock;

        try {

            // new root version
            $this->testShiftRecursive();
            $box = new BoxMock;
            $this->group = new GroupMock;
            $box->group = $this->group;
            $box->bus = new BusMock;
            $box->log = new LogMock;

            // new root with new cache dir
            $this->testShiftRecursiveCache();
            $this->group = new GroupMock;
            $box->group = $this->group;
            unset($box->dir);// clear

            // new root with new cache dir intersection
            $this->testShiftRecursiveCacheIntersection();
            $this->group = new GroupMock;
            $box->group = $this->group;
            unset($box->dir);// clear

            $this->testShiftNested();
            $this->group = new GroupMock;
            $box->group = $this->group;
            unset($box->dir);// clear

            // check if persisted inside "other" dir
            $this->testShiftRecursiveWithExecutedFiles();
            $this->group = new GroupMock;
            $box->group = $this->group;
            unset($box->dir);// clear

            $this->testShiftNestedWithExecutedFiles();


        } catch (Exception) {
            $this->handleFailedTest();
        }

        $box::unsetInstance();
    }

    public function testShiftRecursive(): void
    {
        $this->setUp(__DIR__ . '/Mocks/package/recursive');

        $this->group->hasDownloadable = true;
        $this->group->internalMetas["metadata1"] = new InternalMetadataMock(
            InternalCategory::OBSOLETE, [
            "id" => "metadata1",
            "name" => "metadata1",
            "description" => "metadata1",
            "version" => "1.0.0",
            "dir" => "",
            "source" => "",
            "structure" => [
                "cache" => "/cache"
            ]
        ]);

        $this->group->internalRoot = $this->group->internalMetas["metadata1"];
        $this->group->externalMetas["metadata1"] = new ExternalMetadataMock(
            ExternalCategory::DOWNLOADABLE, [
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
            "dir" => "",
            "structure" => [
                "cache" => "/cache"
            ]
        ]);

        $this->group->externalRoot = $this->group->externalMetas["metadata1"];

        (new Shift([]))->execute();

        if (!file_exists("$this->cache/new") ||
            !file_exists("$this->cache/cache/new") ||
            !file_exists("$this->cache/cache/log/keep"))
            $this->handleFailedTest();
    }

    public function testShiftRecursiveCache(): void
    {
        $this->setUp(__DIR__ . '/Mocks/package/cache');
        $this->group->hasDownloadable = true;
        $this->group->internalMetas["metadata1"] = new InternalMetadataMock(
            InternalCategory::OBSOLETE, [
            "id" => "metadata1",
            "name" => "metadata1",
            "description" => "metadata1",
            "version" => "1.0.0",
            "dir" => "",
            "source" => "",
            "structure" => [
                "cache" => "/cache"
            ]
        ]);

        $this->group->internalRoot = $this->group->internalMetas["metadata1"];
        $this->group->externalMetas["metadata1"] = new ExternalMetadataMock(
            ExternalCategory::DOWNLOADABLE, [
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
            "dir" => "",
            "structure" => [
                "cache" => "/che"
            ]
        ]);

        $this->group->externalRoot = $this->group->externalMetas["metadata1"];
        (new Shift([]))->execute();

        if (file_exists("$this->cache/cache") || // old cache dir
            !file_exists("$this->cache/new") ||
            !file_exists("$this->cache/che/new") ||
            !file_exists("$this->cache/che/log/keep"))
            $this->handleFailedTest();
    }

    public function testShiftRecursiveCacheIntersection(): void
    {
        $this->setUp(__DIR__ . '/Mocks/package/cache_intersecion');
        $this->group->hasDownloadable = true;
        $this->group->internalMetas["metadata1"] = new InternalMetadataMock(
            InternalCategory::OBSOLETE, [
            "id" => "metadata1",
            "name" => "metadata1",
            "description" => "metadata1",
            "version" => "1.0.0",
            "dir" => "",
            "source" => "",
            "structure" => [
                "cache" => "/cache"
            ]
        ]);

        $this->group->internalRoot = $this->group->internalMetas["metadata1"];
        $this->group->externalMetas["metadata1"] = new ExternalMetadataMock(
            ExternalCategory::DOWNLOADABLE, [
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
            "dir" => "",
            "structure" => [
                "cache" => "/cache/new"
            ]
        ]);

        $this->group->externalRoot = $this->group->externalMetas["metadata1"];
       (new Shift([]))->execute();

        if (!file_exists("$this->cache/cache/new") ||
            !file_exists("$this->cache/cache/new/new") ||
            !file_exists("$this->cache/cache/new/log/keep"))
            $this->handleFailedTest();
    }

    public function testShiftNested(): void
    {
        $this->setUp(__DIR__ . '/Mocks/package/nested');
        $this->group->hasDownloadable = true;
        $this->group->internalMetas["metadata1"] = new InternalMetadataMock(
            InternalCategory::RECYCLABLE, [
            "id" => "metadata1",
            "name" => "metadata1",
            "description" => "metadata1",
            "version" => "1.0.0",
            "dir" => "",
            "source" => __DIR__ . "/cache",
            "structure" => [
                "cache" => "/cache",
                "extensions" => [],
                "states" => []
            ]
        ]);
        $this->group->internalRoot = $this->group->internalMetas["metadata1"];
        $this->group->internalMetas["metadata3"] = new InternalMetadataMock(
            InternalCategory::OBSOLETE, [
            "id" => "metadata3",
            "name" => "metadata3",
            "description" => "metadata3",
            "version" => "1.0.0",
            "source" => __DIR__ . "/cache/dependencies/metadata3",
            "dir" => "/dependencies/metadata3",
            "structure" => [
                "cache" => "/cache"
            ]
        ]);
        $this->group->externalMetas["metadata1"] = new ExternalMetadataMock(
            ExternalCategory::REDUNDANT, [
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
            "dir" => "",
            "structure" => [
                "cache" => "/cache"
            ]
        ]);

        $this->group->externalRoot = $this->group->externalMetas["metadata1"];
        $this->group->externalMetas["metadata1"] = new ExternalMetadataMock(
            ExternalCategory::DOWNLOADABLE, [
            "id" => "metadata3",
            "name" => "metadata3",
            "description" => "metadata3",
            "version" => "1.0.0",
            "source" => [
                "api" => "",
                "path" => "",
                "prefix" => "",
                "reference" => ""
            ],
            "dir" => "/dependencies/metadata3",
            "structure" => [
                "cache" => "/cache"
            ]
        ]);
        (new Shift([]))->execute();

        if (!file_exists("$this->cache/old") ||
            !file_exists("$this->cache/dependencies/metadata3/new") ||
            !file_exists("$this->cache/cache/new") ||
            !file_exists("$this->cache/cache/log/keep"))
            $this->handleFailedTest();
    }

    public function testShiftRecursiveWithExecutedFiles(): void
    {
        $this->setUp(__DIR__ . '/Mocks/package/recursive_executed');
        $this->group->hasDownloadable = true;
        $this->group->internalMetas["valvoid/fusion"] = new InternalMetadataMock(
            InternalCategory::OBSOLETE, [
            "id" => "valvoid/fusion",
            "name" => "metadata1",
            "description" => "metadata1",
            "version" => "1.0.0",
            "dir" => "",
            "source" => __DIR__ . "/cache", // outside mocks
            "structure" => [
                "cache" => "/cache",
                "sources" => []
            ]
        ]);
        $this->group->internalRoot = $this->group->internalMetas["valvoid/fusion"];
        $this->group->externalMetas["valvoid/fusion"] = new ExternalMetadataMock(
            ExternalCategory::DOWNLOADABLE, [
            "id" => "valvoid/fusion",
            "name" => "metadata1",
            "description" => "metadata1",
            "version" => "1.0.0",
            "source" => [
                "api" => "",
                "path" => "",
                "prefix" => "",
                "reference" => ""
            ],
            "dir" => "",
            "structure" => [
                "cache" => "/cache"
            ]
        ]);

        $this->group->externalRoot = $this->group->externalMetas["valvoid/fusion"];
        $reflection = new ReflectionClass(Shift::class);
        $task = $reflection->newInstance([]);
        $reflection->getProperty("dir")->setValue($task, __DIR__ . '/cache');

        $handle = fopen("$this->cache/fusion", 'r');
        $other = "$this->cache/cache/other/valvoid/fusion";

        if ($handle !== false &&
            fgets($handle) == "old") {
            $task->execute();

            if (rewind($handle) !== false &&
                fgets($handle) == "new" &&
                fclose($handle) !== false &&
                file_exists("$this->cache/new") &&
                file_exists("$this->cache/cache/log/keep") &&
                file_exists("$this->cache/cache/new") &&

                // persisted session
                file_exists("$other/old") &&
                file_exists("$other/fusion") &&
                file_get_contents("$other/fusion") == "old" &&
                file_exists("$other/cache/log/keep") &&
                file_exists("$other/cache/old")) {

                return;
            }
        }

        $this->handleFailedTest();
    }

    public function testShiftNestedWithExecutedFiles(): void
    {
        $this->setUp(__DIR__ . '/Mocks/package/nested_executed');
        $this->group->hasDownloadable = true;
        $this->group->internalMetas["metadata1"] = new InternalMetadataMock(
            InternalCategory::RECYCLABLE, [
            "id" => "metadata1",
            "name" => "metadata1",
            "description" => "metadata1",
            "version" => "1.0.0",
            "dir" => "",
            "source" => __DIR__ . "/cache", // outside mocks
            "structure" => [
                "cache" => "/cache",
                "extensions" => [],
                "states" => []
            ]
        ]);
        $this->group->internalRoot = $this->group->internalMetas["metadata1"];
        $this->group->internalMetas["valvoid/fusion"] = new InternalMetadataMock(
            InternalCategory::OBSOLETE, [
            "id" => "valvoid/fusion",
            "name" => "metadata3",
            "description" => "metadata3",
            "version" => "1.0.0",
            "source" => __DIR__ . "/cache/dependencies/valvoid/fusion", // outside mocks
            "dir" => "/dependencies/valvoid/fusion",
            "structure" => [
                "cache" => "/cache"
            ]
        ]);
        $this->group->externalMetas["metadata1"] = new ExternalMetadataMock(
            ExternalCategory::REDUNDANT, [
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
            "dir" => "",
            "structure" => [
                "cache" => "/cache"
            ]
        ]);

        $this->group->externalRoot = $this->group->externalMetas["metadata1"];
        $this->group->externalMetas["valvoid/fusion"] = new ExternalMetadataMock(
            ExternalCategory::DOWNLOADABLE,  [
            "id" => "valvoid/fusion",
            "name" => "metadata3",
            "description" => "metadata3",
            "version" => "1.0.0",
            "source" => [
                "api" => "",
                "path" => "",
                "prefix" => "",
                "reference" => ""
            ],
            "dir" => "/dependencies/valvoid/fusion",
            "structure" => [
                "cache" => "/cache"
            ]
        ]);

        $reflection = new ReflectionClass(Shift::class);
        $task = $reflection->newInstance([]);
        $reflection->getProperty("dir")->setValue($task, __DIR__ . '/cache');

        $handle = fopen("$this->cache/dependencies/valvoid/fusion/fusion", 'r');
        $other = "$this->cache/cache/other/valvoid/fusion";

        if ($handle !== false &&
            fgets($handle) == "old") {
            $task->execute();

            if (rewind($handle) !== false &&
                fgets($handle) == "new" &&
                fclose($handle) !== false &&
                file_exists("$this->cache/old") &&
                file_exists("$this->cache/cache/log/keep") &&
                file_exists("$this->cache/cache/new") &&

                // persisted session
                file_exists("$other/old") &&
                file_exists("$other/fusion") &&
                file_get_contents("$other/fusion") == "old" &&
                file_exists("$other/cache/old")) {

                return;
            }
        }

        $this->handleFailedTest();
    }

    private function setUp(string $dir): void
    {
        $this->delete($this->cache);

        if (!mkdir($this->cache, 0755))
            throw new Exception(
                "Can't create the directory \"cache\"."
            );


        $this->copy($dir, $this->cache);
    }


    private function delete(string $file): void
    {
        if (is_dir($file)) {
            foreach (scandir($file, SCANDIR_SORT_NONE) as $filename)
                if ($filename != "." && $filename != "..")
                    $this->delete("$file/$filename");

            rmdir($file);

        } elseif (is_file($file))
            unlink($file);
    }

    /**
     * @throws Exception
     */
    private function copy(string $from, string $to): void
    {
        foreach (scandir($from, SCANDIR_SORT_NONE) as $filename)
            if ($filename != "." && $filename != "..") {
                $file = "$from/$filename";
                $copy = "$to/$filename";

                if (is_file($file)) {
                    if (!copy($file, $copy))
                        throw new Exception(
                            "Can't copy the file \"$file\" to \"$copy\"."
                        );

                } else {
                    if (!mkdir($copy, 0755, true))
                        throw new Exception(
                            "Can't create the directory \"$copy\"."
                        );

                    $this->copy($file, $copy);
                }
            }
    }
}