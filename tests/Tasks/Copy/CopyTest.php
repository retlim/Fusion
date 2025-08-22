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

namespace Valvoid\Fusion\Tests\Tasks\Copy;

use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Tasks\Copy\Copy;
use Valvoid\Fusion\Tests\Tasks\Copy\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Copy\Mocks\ExternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Copy\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Copy\Mocks\InternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Copy\Mocks\LogMock;
use Valvoid\Fusion\Tests\Test;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;

/**
 * Integration test case for the copy task.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class CopyTest extends Test
{
    protected string|array $coverage = Copy::class;

    private string $cache = __DIR__ . "/Mocks/package/cache/packages";

    public function __construct()
    {
        $box = new BoxMock;
        $box->log = new LogMock;
        $group = new GroupMock;
        $box->group = $group;
        $group->hasDownloadable = true;

        $group->internalMetas["metadata1"] = new InternalMetadataMock(
            InternalCategory::RECYCLABLE, [
            "id" => "metadata1",
            "name" => "metadata1",
            "description" => "metadata1",
            "source" => __DIR__ . "/Mocks/package",
            "dir" => __DIR__ . "/Mocks/package", // project root
            "version" => "1.0.0",
            "structure" => [
                "cache" => "/cache",
                "extensions" => [],
                "sources" => [

                    // lock - do not copy
                    // its other package content
                    "/dependencies" => [
                        "metadata2",
                        "metadata3"
                    ]
                ]
            ]
        ]);

        $group->internalMetas["metadata2"] = new InternalMetadataMock(
            InternalCategory::MOVABLE, [
            "id" => "metadata2",
            "name" => "metadata2",
            "description" => "metadata2",
            "source" => __DIR__ . "/Mocks/package/dependencies/metadata2",
            "dir" => __DIR__ . "/Mocks/package/dependencies/metadata2",
            "version" => "1.0.0",
            "structure" => [
                "cache" => "/cache",
                "extensions" => [],
                "sources" => []
            ]
        ]);

        $group->internalMetas["metadata3"] = new InternalMetadataMock(
            InternalCategory::OBSOLETE, [
            "id" => "metadata3",
            "name" => "metadata3",
            "description" => "metadata3",
            "source" => __DIR__ . "/Mocks/package/dependencies/metadata3",
            "dir" => __DIR__ . "/Mocks/package/dependencies/metadata3",
            "version" => "1.0.0",
            "structure" => [
                "cache" => "/cache",
                "extensions" => [],
                "sources" => []
            ]
        ]);

        $group->externalMetas["metadata3"] = new ExternalMetadataMock(
            ExternalCategory::DOWNLOADABLE, [
            "id" => "metadata3",
            "name" => "metadata3",
            "description" => "metadata3",
            "source" => "metadata3",
            "dir" => __DIR__ . "/Mocks/package/dep/metadata3",
            "version" => "1.0.1",
            "structure" => [
                "cache" => "/cache",
                "extensions" => [],
                "sources" => []
            ]
        ]);

        $this->testTargetCacheDirectory();

        $box::unsetInstance();
    }

    public function testTargetCacheDirectory(): void
    {
        $copy = new Copy([]);
        $copy->execute();

        if (is_dir($this->cache)) {
            $filenames = $this->getFilenames($this->cache);

            $assert = [
                __DIR__ . "/Mocks/package/cache/packages/metadata1",
                __DIR__ . "/Mocks/package/cache/packages/metadata1/metadata1",
                __DIR__ . "/Mocks/package/cache/packages/metadata2",
                __DIR__ . "/Mocks/package/cache/packages/metadata2/metadata2"
            ];

            if ($filenames == $assert)
                return;
        }

        $this->handleFailedTest();
    }

    private function getFilenames(string $dir): array
    {
        $content = [];

        if (is_dir($dir)) {
            $filenames = scandir($dir);

            if ($filenames !== false) {
                foreach ($filenames as $filename) {
                    if ($filename === '.' || $filename === '..')
                        continue;

                    $file = $dir . '/' . $filename;
                    $content[] = $file;

                    if (is_dir($file))
                        $content = array_merge($content, $this->getFilenames($file));
                }
            }
        }

        return $content;
    }
}