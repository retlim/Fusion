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

namespace Valvoid\Fusion\Tests\Tasks\Copy\Mocks;

use ReflectionClass;
use ReflectionException;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Metadata\External\External;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;
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
    public static function addMockedMetadata(): void
    {
        $internal = [];
        $reflection = new ReflectionClass(Internal::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');

        // copy
        $metadata->setCategory(InternalCategory::RECYCLABLE);
        $content->setValue($metadata, [
            "id" => "metadata1",
            "name" => "metadata1",
            "description" => "metadata1",
            "source" => __DIR__ . "/package",
            "dir" => __DIR__ . "/package", // project root
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

        $internal["metadata1"] = $metadata;

        $reflection = new ReflectionClass(Internal::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');

        // copy
        $metadata->setCategory(InternalCategory::MOVABLE);
        $content->setValue($metadata, [
            "id" => "metadata2",
            "name" => "metadata2",
            "description" => "metadata2",
            "source" => __DIR__ . "/package/dependencies/metadata2",
            "dir" => __DIR__ . "/package/dependencies/metadata2",
            "version" => "1.0.0",
            "structure" => [
                "cache" => "/cache",
                "extensions" => [],
                "sources" => []
            ]
        ]);

        $internal["metadata2"] = $metadata;

        $reflection = new ReflectionClass(Internal::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');

        // do no copy
        $metadata->setCategory(InternalCategory::OBSOLETE);
        $content->setValue($metadata, [
            "id" => "metadata3",
            "name" => "metadata3",
            "description" => "metadata3",
            "source" => __DIR__ . "/package/dependencies/metadata3",
            "dir" => __DIR__ . "/package/dependencies/metadata3",
            "version" => "1.0.0",
            "structure" => [
                "cache" => "/cache",
                "extensions" => [],
                "sources" => []
            ]
        ]);

        $internal["metadata3"] = $metadata;

        Group::setInternalMetas($internal);

        $reflection = new ReflectionClass(External::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');

        $metadata->setCategory(ExternalCategory::DOWNLOADABLE);
        $content->setValue($metadata, [
            "id" => "metadata3",
            "name" => "metadata3",
            "description" => "metadata3",
            "source" => "metadata3",
            "dir" => __DIR__ . "/package/dep/metadata3",
            "version" => "1.0.1",
            "structure" => [
                "cache" => "/cache",
                "extensions" => [],
                "sources" => []
            ]
        ]);

        Group::setExternalMetas(["metadata3" => $metadata]);
    }
}