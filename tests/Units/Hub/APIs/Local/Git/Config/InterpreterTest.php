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

namespace Valvoid\Fusion\Tests\Units\Hub\APIs\Local\Git\Config;

use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Config;
use Valvoid\Fusion\Hub\APIs\Local\Git\Config\Interpreter;
use Valvoid\Fusion\Hub\APIs\Local\Git\Git;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Reflex\Test\Wrapper;

class InterpreterTest extends Wrapper
{
    public function testDefaultApi(): void
    {
        $bus = $this->createStub(Bus::class);
        $interpreter = new Interpreter($bus);

        $interpreter->interpret([], Git::class);
    }

    public function testConfiguredApi(): void
    {
        $bus = $this->recycleStub(Bus::class);
        $interpreter = new Interpreter($bus);

        $interpreter->interpret([], [
            "api" => Git::class
        ]);
    }

    public function testWrapperTypeError(): void
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