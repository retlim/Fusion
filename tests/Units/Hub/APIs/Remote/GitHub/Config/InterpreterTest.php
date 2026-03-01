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

namespace Valvoid\Fusion\Tests\Units\Hub\APIs\Remote\GitHub\Config;

use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Config;
use Valvoid\Fusion\Hub\APIs\Remote\GitHub\GitHub;
use Valvoid\Fusion\Hub\APIs\Remote\GitHub\Config\Interpreter;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Reflex\Test\Wrapper;

class InterpreterTest extends Wrapper
{
    public function testConfiguredApi(): void
    {
        $bus = $this->createStub(Bus::class);
        $interpreter = new Interpreter($bus);

        $interpreter->interpret([], [
            "api" => GitHub::class,
            "protocol" => "http",
            "domain" => "github.com",
            "tokens" => [
                "token"
            ]
        ]);
    }

    public function testDefaultApi(): void
    {
        $bus = $this->recycleStub(Bus::class);
        $interpreter = new Interpreter($bus);

        $interpreter->interpret([], GitHub::class);
    }

    public function testReset(): void
    {
        $bus = $this->recycleStub(Bus::class);
        $interpreter = new Interpreter($bus);

        $interpreter->interpret([], null);
    }

    public function testInvalid(): void
    {
        $bus = $this->createMock(Bus::class);
        $interpreter = new Interpreter($bus);

        $bus->fake("broadcast")
            ->hook(function (Config $event) {
                $this->validate($event->getLevel())
                    ->as(Level::ERROR);

                $this->validate($event->getBreadcrumb())
                    ->as(["###"]);
            });

        $interpreter->interpret(["###"], 34);
    }

}