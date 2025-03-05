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

namespace Valvoid\Fusion\Tests\Tasks\Snap\Mocks;

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
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class MetadataMock
{
    /**
     * @throws ReflectionException
     */
    public static function addRedundantMockedMetadata(): void
    {
        $external = [];
        $reflection = new ReflectionClass(External::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');

        $metadata->setCategory(ExternalCategory::REDUNDANT);
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

        $external["metadata1"] = $metadata;

        $reflection = new ReflectionClass(External::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');
        $layers = $reflection->getProperty('layers');

        // do no copy
        $metadata->setCategory(ExternalCategory::REDUNDANT);
        $layers->setValue($metadata, ["object" => [
            "version" => "3.2.1" // pseudo version for offset
        ]]);
        $content->setValue($metadata, [
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
        ]);

        $external["metadata2"] = $metadata;

        $reflection = new ReflectionClass(External::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');
        $layers = $reflection->getProperty('layers');

        $metadata->setCategory(ExternalCategory::REDUNDANT);
        $layers->setValue($metadata, []);
        $content->setValue($metadata, [
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

        $external["metadata3"] = $metadata;

        Group::setExternalMetas($external);
        Group::setImplication([
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
        ]);
    }

    /**
     * @throws ReflectionException
     */
    public static function addDownloadableMockedMetadata(): void
    {
        $reflection = new ReflectionClass(Internal::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');

        $metadata->setCategory(InternalCategory::RECYCLABLE);
        $content->setValue($metadata, [
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

        Group::setInternalMetas(["metadata1" => $metadata]);

        $external = [];
        $reflection = new ReflectionClass(External::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');
        $layers = $reflection->getProperty('layers');

        // trigger cache update
        $metadata->setCategory(ExternalCategory::DOWNLOADABLE);
        $layers->setValue($metadata, []);
        $content->setValue($metadata, [
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

        $external["metadata2"] = $metadata;

        $reflection = new ReflectionClass(External::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');
        $layers = $reflection->getProperty('layers');

        $metadata->setCategory(ExternalCategory::REDUNDANT);
        $layers->setValue($metadata, ["object" => [
            "version" => "5.4.3" // pseudo version for offset
        ]]);
        $content->setValue($metadata, [
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
        ]);

        $external["metadata3"] = $metadata;

        Group::setExternalMetas($external);
        Group::setImplication([
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
        ]);
    }
}