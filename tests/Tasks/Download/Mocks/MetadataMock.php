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

namespace Valvoid\Fusion\Tests\Tasks\Download\Mocks;

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
        $reflection = new ReflectionClass(External::class);
        $metadata = $reflection->newInstanceWithoutConstructor();
        $content = $reflection->getProperty('content');
        $layers = $reflection->getProperty('layers');
        $layers->setValue($metadata, [
            "object" => [
                "version" => "3.4.5"
            ]
        ]);
        $metadata->setCategory(ExternalCategory::DOWNLOADABLE);
        $content->setValue($metadata, [
            "id" => "metadata1",
            "name" => "metadata1",
            "description" => "metadata1",
            "source" => [
                "api" => "",
                "path" => "",
                "prefix" => "",
                "reference" => ""
            ],
            "dir" => __DIR__ . "/package/dep/metadata1",
            "version" => "1.0.1",
            "structure" => [
                "cache" => "/cache",
                "extensions" => [],
                "sources" => []
            ]
        ]);

        Group::setExternalMetas(["metadata1" => $metadata]);
    }
}