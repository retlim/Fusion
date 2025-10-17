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

namespace Valvoid\Fusion\Tests\Hub;

use Valvoid\Fusion\Hub\Parser;
use Valvoid\Fusion\Tests\Test;

class ParserTest extends Test
{
    protected string|array $coverage = Parser::class;

    public function __construct()
    {
        $this->testOffsets();
    }

    public function testOffsets(): void
    {
        if (Parser::getOffsets([
            [
                "major" => "1",
                "minor" => "0",
                "patch" => "0",
                "release" => "",
                "build" => "",
                "offset" => "1",
                "sign" => "=="

            ], "||", [
                [
                    "major" => "2",
                    "minor" => "0",
                    "patch" => "0",
                    "release" => "",
                    "build" => "",
                    "sign" => ">="
                ], "&&", [
                    [
                        "major" => "3",
                        "minor" => "0",
                        "patch" => "0",
                        "release" => "",
                        "build" => "",
                        "offset" => "2",
                        "sign" => "=="
                    ], "||", [
                        "major" => "4",
                        "minor" => "0",
                        "patch" => "0",
                        "release" => "",
                        "build" => "",
                        "offset" => "3",
                        "sign" => "=="
                    ]
                ]
            ]
        ]) !== [
                [
                    "version" => "1.0.0",
                    "entry" => [
                        "major" => "1",
                        "minor" => "0",
                        "patch" => "0",
                        "release" => "",
                        "build" => "",
                        "offset" => "1",
                        "sign" => "=="
                    ]
                ],
                [
                    "version" => "3.0.0",
                    "entry" => [
                        "major" => "3",
                        "minor" => "0",
                        "patch" => "0",
                        "release" => "",
                        "build" => "",
                        "offset" => "2",
                        "sign" => "=="
                    ]
                ],
                [
                    "version" => "4.0.0",
                    "entry" => [
                        "major" => "4",
                        "minor" => "0",
                        "patch" => "0",
                        "release" => "",
                        "build" => "",
                        "offset" => "3",
                        "sign" => "=="
                    ]
                ],
            ])
            $this->handleFailedTest();
    }
}