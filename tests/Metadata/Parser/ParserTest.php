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

namespace Valvoid\Fusion\Tests\Metadata\Parser;

use Valvoid\Fusion\Metadata\Parser\Parser;
use Valvoid\Fusion\Tests\Test;

/**
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class ParserTest extends Test
{
    protected string|array $coverage = Parser::class;

    public function __construct()
    {
        $this->testParse();
    }

    public function testParse(): void
    {
        $metadata = [
            "structure" => [
                "/cache" => [
                    "cache",
                    "/loadable/any" => "space\\"
                ]
            ],
            "environment" => [
                "php" => [
                    "version" => "1.0.0"
                ]
            ]
        ];

        Parser::parse($metadata);

        if ($metadata != [
                "structure" => [
                    "/cache" => [
                        "cache",
                        "/loadable" => [
                            "/any" => ["space\\"]
                        ]
                    ]
                ],
                "environment" => [
                    "php" => [
                        "version" => [[
                            "major" => "1",
                            "minor" => "0",
                            "patch" => "0",
                            "build" => "",
                            "release" => "",
                            "sign" => ""
                        ]]
                    ]
                ]
            ]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}