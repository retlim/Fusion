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

namespace Valvoid\Fusion\Tests\Metadata\Normalizer;

use Valvoid\Fusion\Metadata\Normalizer\Structure;
use Valvoid\Fusion\Tests\Test;

/**
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class StructureTest extends Test
{
    protected string|array $coverage = Structure::class;

    public function __construct()
    {
        $this->testNormalize();
    }

    public function testNormalize(): void
    {
        $metadata = [
            "id" => "",
            "version" => "",
            "name" => "",
            "dir" => "",
            "description" => "",
            "structure" => [
                "recursive",
                "/cache" => [
                    "cache",
                    "/loadable/path3" => "namespace\\any"
                ],
                "/path2" => "extension",
                "/path4" => "state",
                "/path1" => [
                    "source1",
                    "source2",
                ]
            ],
            "environment" => []
        ];

        Structure::normalize($metadata, "layer");

        if ($metadata !== [
                "id" => "",
                "version" => "",
                "name" => "",
                "dir" => "",
                "description" => "",
                "structure" => [
                    "cache" => "/cache",
                    "sources" => [
                        "" => ["recursive"],
                        "/path1" => [
                            "source1",
                            "source2",
                        ]
                    ],
                    "extensions" => [
                        "/path2"
                    ],
                    "namespaces" => [
                        "namespace\\any" => "/path3"
                    ],
                    "states" => [
                        "/path4"
                    ]
                ],
                "environment" => []
            ]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}