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

namespace Valvoid\Fusion\Tests\Tasks\Replicate\Mocks;

use ReflectionClass;
use ReflectionException;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Tasks\Group;

/**
 * Mocked internal and external metadata.
 *
 * @Copyright Valvoid
 * @license GNU GPLv3
 */
class MetadataMock
{
    /**
     * @throws ReflectionException
     */
    public static function addRootMetadata(): void
    {
        $reflection = new ReflectionClass(Internal::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty("content");
        $content->setValue($metadata, [
            "id" => "metadata1",
            "name" => "metadata1",
            "description" => "metadata1",
            "source" => __DIR__ . "/package",
            "dir" => "", // relative to root dir
            "version" => "1.0.0",
            "structure" => [
                "cache" => "/cache",
                "namespaces" => [],
                "sources" => [
                    "/dependencies" => [

                        // api/path/ref
                        "valvoid.com/test/local/1.0.0",
                        "valvoid.com/test/development/1.0.0",
                        "valvoid.com/test/production/1.0.0"
                    ]
                ],
            ]
        ]);

        Group::setInternalMetas(["metadata1" => $metadata]);
    }

    public static function get(string $id): string
    {
        return json_encode([
            "id" => "test/$id",
            "name" => $id,
            "description" => $id,
            "version" => "1.0.0",
            "structure" => [
                "/cache" => "cache"
            ],
            "environment" => [
                "php" => [
                    "version" => "8.1.0",
                ]
            ]
        ]);
    }
}