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

namespace Valvoid\Fusion\Tests\Units\Metadata\Parser;

use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Metadata\Parser\Environment;
use Valvoid\Reflex\Test\Wrapper;
use Valvoid\Fusion\Metadata\Interpreter\Environment as EnvironmentInterpreter;

class EnvironmentTest extends Wrapper
{
    public function testParse(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createStub(Bus::class);
        $interpreter = $this->createMock(EnvironmentInterpreter::class);
        $environment = new Environment(
            box: $box,
            bus: $bus
        );

        $env = [
            "php" => [
                "modules" => ["mod1", "mod2"],
                "version" => "1.0.0 || (>=3.4.5 && <=4.0.0)"
            ]
        ];

        $box->fake("get")
            ->expect(class: EnvironmentInterpreter::class)
            ->return($interpreter)
            ->repeat(2);

        $interpreter->fake("isSemanticVersionCorePattern")
            ->return(true)
            ->repeat(2);

        $environment->parse($env);
        $this->validate($env)
            ->as([
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
                ]]);
    }
}