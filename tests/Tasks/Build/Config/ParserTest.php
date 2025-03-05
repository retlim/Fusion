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

namespace Valvoid\Fusion\Tests\Tasks\Build\Config;

use Valvoid\Fusion\Tasks\Build\Config\Parser;
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
        $this->testPhpVersion();
    }

    public function testPhpVersion(): void
    {
        $config["environment"]["php"]["version"] = "1.23.4-beta";
        $assertion["environment"]["php"]["version"] = [
            "build" => "",
            "release" => "beta",
            "major" => "1",
            "minor" => "23",
            "patch" => "4"
        ];

        Parser::parse([], $config);

        if ($config !== $assertion) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}