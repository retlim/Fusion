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
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class MetadataMock
{
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