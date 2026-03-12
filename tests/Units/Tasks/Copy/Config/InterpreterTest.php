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

namespace Valvoid\Fusion\Tests\Units\Tasks\Copy\Config;

use Exception;
use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Config;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Tasks\Copy\Copy;
use Valvoid\Fusion\Tasks\Copy\Config\Interpreter;
use Valvoid\Reflex\Test\Wrapper;

class InterpreterTest extends Wrapper
{
    public function testReset(): void
    {
        $box = $this->createStub(Box::class);
        $bus = $this->createStub(Bus::class);
        $interpreter = new Interpreter(
            box: $box,
            bus: $bus
        );

        $interpreter->interpret([], null);
    }

    public function testInvalidType(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $config = $this->createMock(Config::class);
        $interpreter = new Interpreter(
            box: $box,
            bus: $bus
        );

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($config) {
                $this->validate($class)
                    ->as(Config::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                return $config;
            });

        $bus->fake("broadcast")
            ->hook(function ($event) use ($config) {
                $this->validate($event)
                    ->as($config);

                throw new Exception;
            });

        $this->expectException(Exception::class);
        $interpreter->interpret([], 3455);
    }

    public function testDefault(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $interpreter = new Interpreter(
            box: $box,
            bus: $bus
        );

        $interpreter->interpret([], Copy::class);
    }

    public function testConfigured(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $interpreter = new Interpreter(
            box: $box,
            bus: $bus
        );

        $interpreter->interpret([], [
            "task" => Copy::class
        ]);
    }
}