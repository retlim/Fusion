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

namespace Valvoid\Fusion\Tests\Metadata\Parser;

use Valvoid\Fusion\Metadata\Parser\Environment;
use Valvoid\Fusion\Tests\Test;

/**
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class EnvironmentTest extends Test
{
    protected string|array $coverage = Environment::class;

    public function __construct()
    {
        $this->testParse();
    }

    public function testParse(): void
    {
        $environment = [
            "php" => [
                "modules" => ["mod1", "mod2"],
                "version" => "1.0.0 || (>=3.4.5 && <=4.0.0)"
            ]
        ];

        Environment::parse($environment);

        if ($environment != [
            "php" => [
                "modules" => ["mod1", "mod2"],
                "version" => [[
                    "major" => "1",
                    "minor" => "0",
                    "patch" => "0",
                    "build" => "",
                    "release" => "",
                    "sign" => ""
                ], "||", [
                    [
                        "major" => "3",
                        "minor" => "4",
                        "patch" => "5",
                        "build" => "",
                        "release" => "",
                        "sign" => ">="
                    ], "&&", [
                        "major" => "4",
                        "minor" => "0",
                        "patch" => "0",
                        "build" => "",
                        "release" => "",
                        "sign" => "<="
                    ]
                ]]
            ]]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}