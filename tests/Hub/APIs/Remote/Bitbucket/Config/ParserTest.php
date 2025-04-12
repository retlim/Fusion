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

namespace Valvoid\Fusion\Tests\Hub\APIs\Remote\Bitbucket\Config;

use Valvoid\Fusion\Hub\APIs\Remote\Bitbucket\Config\Parser;
use Valvoid\Fusion\Tests\Test;

/**
 * Config parser test.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ParserTest extends Test
{
    /** @var string|array Code coverage. */
    protected string|array $coverage = Parser::class;

    public function __construct()
    {
        $this->testParse();
    }

    public function testParse(): void
    {
        $config["tokens"] = [
            "token1",
            "/valvoid" => [
                "/mosaic/code/token2",
                "/fusion" => [
                    "token3",
                    "/php/code/token4"
                ]
            ]
        ];

        Parser::parse([], $config);

        if ($config["tokens"] !== [
                "token1",
                "valvoid" => [
                    "mosaic" => [
                        "code" => [
                            "token2"
                        ]
                    ],
                    "fusion" => [
                        "token3",
                        "php" => [
                            "code" => [
                                "token4"
                            ]
                        ]
                    ]
                ]
            ]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}