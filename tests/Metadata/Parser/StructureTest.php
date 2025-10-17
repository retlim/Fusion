<?php
/*
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
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Valvoid\Fusion\Tests\Metadata\Parser;

use Valvoid\Fusion\Metadata\Parser\Structure;
use Valvoid\Fusion\Tests\Test;

class StructureTest extends Test
{
    protected string|array $coverage = Structure::class;

    public function __construct()
    {
        $this->testParse();
    }

    public function testParse(): void
    {
        $metadata = [
            "/cache" => [
                "cache",
                "/loadable/any" => "space\\"
            ],
            "/path1" => "state",
            "/path5" => "extension",
            "/path2/path3" => [
                "source1",
                "source2",
                "source/prefix" => [
                    "/path4" => "source/suffix"
                ]
            ],
        ];

        Structure::parse($metadata);

        if ($metadata != [
                "/cache" => [
                    "cache",
                    "/loadable" => [
                        "/any" => ["space\\"]
                    ]
                ],
                "/path1" => ["state"],
                "/path5" => ["extension"],
                "/path2" => [
                    "/path3" => [
                        "source1",
                        "source2",
                        "source" => [
                            "prefix" => [
                                "/path4" => [
                                    "source" => [
                                        "suffix"
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}