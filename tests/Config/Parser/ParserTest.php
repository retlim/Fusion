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

namespace Valvoid\Fusion\Tests\Config\Parser;

use Valvoid\Fusion\Config\Parser\Parser;
use Valvoid\Fusion\Hub\APIs\Remote\Valvoid\Valvoid;
use Valvoid\Fusion\Log\Serializers\Streams\Terminal\Terminal;
use Valvoid\Fusion\Tasks\Inflate\Inflate;
use Valvoid\Fusion\Tests\Test;

/**
 * Config parser test.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ParserTest extends Test
{
    protected string|array $coverage = Parser::class;

    public function __construct()
    {
        $this->testReference();
    }

    public function testReference(): void
    {
        $config = [
            "tasks" => [

                // default task
                "test" => Inflate::class
            ],
            "hub" => [
                "apis" => [

                    // default api
                    "test" => Valvoid::class
                ]
            ],
            "log" => [
                "serializers" => [

                    // default serializer
                    "test" => Terminal::class
                ]
            ]
        ];

        Parser::parse($config);

        $assertion = [
            "tasks" => [

                // configured task
                "test" => [
                    "task" => Inflate::class
                ]
            ],
            "hub" => [
                "apis" => [

                    // configured api
                    "test" => [
                        "api" => Valvoid::class
                    ]
                ]
            ],
            "log" => [
                "serializers" => [

                    // configured serializer
                    "test" => [
                        "serializer" => Terminal::class
                    ]
                ]
            ]
        ];

        if ($config !== $assertion) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}