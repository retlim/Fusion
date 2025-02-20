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

namespace Valvoid\Fusion\Tests\Tasks\Shift\Mocks;

use ReflectionClass;
use ReflectionException;
use Valvoid\Fusion\Metadata\External\Category;
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
    public static function addRecursive(): void
    {
        $reflection = new ReflectionClass(Internal::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');

        $metadata->setCategory(InternalCategory::OBSOLETE);
        $content->setValue($metadata, [
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

        Group::setInternalMetas(["metadata1" => $metadata]);

        $reflection = new ReflectionClass(External::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');

        $metadata->setCategory(Category::DOWNLOADABLE);
        $content->setValue($metadata, [
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

        Group::setExternalMetas(["metadata1" => $metadata]);
    }

    /**
     * @throws ReflectionException
     */
    public static function addRecursiveCache(): void
    {
        $reflection = new ReflectionClass(Internal::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');

        $metadata->setCategory(InternalCategory::OBSOLETE);
        $content->setValue($metadata, [
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

        Group::setInternalMetas(["metadata1" => $metadata]);

        $reflection = new ReflectionClass(External::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');

        $metadata->setCategory(Category::DOWNLOADABLE);
        $content->setValue($metadata, [
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

        Group::setExternalMetas(["metadata1" => $metadata]);
    }

    /**
     * @throws ReflectionException
     */
    public static function addNested(): void
    {
        $internal = [];
        $reflection = new ReflectionClass(Internal::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');

        // keep root
        // refresh cache/generated
        $metadata->setCategory(InternalCategory::RECYCLABLE);
        $content->setValue($metadata, [
            "id" => "metadata1",
            "name" => "metadata1",
            "description" => "metadata1",
            "version" => "1.0.0",
            "dir" => "",
            "source" => dirname(__DIR__) . "/cache", // outside mocks
            "structure" => [
                "cache" => "/cache",
                "extensions" => [],
                "states" => []
            ]
        ]);

        $internal["metadata1"] = $metadata;

        $reflection = new ReflectionClass(Internal::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');
        $metadata->setCategory(InternalCategory::OBSOLETE);
        $content->setValue($metadata, [
            "id" => "metadata3",
            "name" => "metadata3",
            "description" => "metadata3",
            "version" => "1.0.0",
            "source" => dirname(__DIR__) . "/cache/dependencies/metadata3", // outside mocks
            "dir" => "/dependencies/metadata3",
            "structure" => [
                "cache" => "/cache"
            ]
        ]);

        $internal["metadata3"] = $metadata;

        Group::setInternalMetas($internal);

        $external = [];
        $reflection = new ReflectionClass(External::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');

        $metadata->setCategory(Category::REDUNDANT);
        $content->setValue($metadata, [
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

        $external["metadata1"] = $metadata;
        $reflection = new ReflectionClass(External::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');

        // at least one downloadable
        $metadata->setCategory(Category::DOWNLOADABLE);
        $content->setValue($metadata, [
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

        $external["metadata3"] = $metadata;

        Group::setExternalMetas($external);
    }

    /**
     * @throws ReflectionException
     */
    public static function addRecursiveExecuted(): void
    {
        $reflection = new ReflectionClass(Internal::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');

        $metadata->setCategory(InternalCategory::OBSOLETE);
        $content->setValue($metadata, [
            "id" => "valvoid/fusion",
            "name" => "metadata1",
            "description" => "metadata1",
            "version" => "1.0.0",
            "dir" => "",
            "source" => dirname(__DIR__) . "/cache", // outside mocks
            "structure" => [
                "cache" => "/cache",
                "sources" => []
            ]
        ]);

        Group::setInternalMetas(["valvoid/fusion" => $metadata]);

        $reflection = new ReflectionClass(External::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');

        $metadata->setCategory(Category::DOWNLOADABLE);
        $content->setValue($metadata, [
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

        Group::setExternalMetas(["valvoid/fusion" => $metadata]);
    }

    /**
     * @throws ReflectionException
     */
    public static function addNestedExecuted(): void
    {
        $internal = [];
        $reflection = new ReflectionClass(Internal::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');

        // keep root
        // refresh cache/generated
        $metadata->setCategory(InternalCategory::RECYCLABLE);
        $content->setValue($metadata, [
            "id" => "metadata1",
            "name" => "metadata1",
            "description" => "metadata1",
            "version" => "1.0.0",
            "dir" => "",
            "source" => dirname(__DIR__) . "/cache", // outside mocks
            "structure" => [
                "cache" => "/cache",
                "extensions" => [],
                "states" => []
            ]
        ]);

        $internal["metadata1"] = $metadata;

        $reflection = new ReflectionClass(Internal::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');
        $metadata->setCategory(InternalCategory::OBSOLETE);
        $content->setValue($metadata, [
            "id" => "valvoid/fusion",
            "name" => "metadata3",
            "description" => "metadata3",
            "version" => "1.0.0",
            "source" => dirname(__DIR__) . "/cache/dependencies/valvoid/fusion", // outside mocks
            "dir" => "/dependencies/valvoid/fusion",
            "structure" => [
                "cache" => "/cache"
            ]
        ]);

        $internal["valvoid/fusion"] = $metadata;

        Group::setInternalMetas($internal);

        $external = [];
        $reflection = new ReflectionClass(External::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');

        $metadata->setCategory(Category::REDUNDANT);
        $content->setValue($metadata, [
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

        $external["metadata1"] = $metadata;
        $reflection = new ReflectionClass(External::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');

        // at least one downloadable
        $metadata->setCategory(Category::DOWNLOADABLE);
        $content->setValue($metadata, [
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

        $external["valvoid/fusion"] = $metadata;

        Group::setExternalMetas($external);
    }
}