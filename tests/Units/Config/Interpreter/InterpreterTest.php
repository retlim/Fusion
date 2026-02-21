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

namespace Valvoid\Fusion\Tests\Units\Config\Interpreter;

use Exception;
use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Config;
use Valvoid\Fusion\Config\Interpreter\Hub;
use Valvoid\Fusion\Config\Interpreter\Interpreter;
use Valvoid\Fusion\Config\Interpreter\Log;
use Valvoid\Fusion\Config\Interpreter\Persistence;
use Valvoid\Fusion\Config\Interpreter\Tasks;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Reflex\Test\Wrapper;

class InterpreterTest extends Wrapper
{
    public function testReset(): void
    {
        $box = $this->createStub(Box::class);
        $bus = $this->createStub(Bus::class);
        $interpreter = new Interpreter($box, $bus);

        $interpreter->interpret(null);
    }

    public function testValidEntryKeys(): void
    {
        $box = $this->createMock(Box::class);
        $hub = $this->createMock(Hub::class);
        $persistence = $this->createMock(Persistence::class);
        $tasks = $this->createMock(Tasks::class);
        $log = $this->createMock(Log::class);
        $bus = $this->createStub(Bus::class);
        $interpreter = new Interpreter($box, $bus);

        $box->fake("get")
            ->expect(class: Hub::class)
            ->return($hub)
            ->expect(class: Persistence::class)
            ->return($persistence)
            ->expect(class: Tasks::class)
            ->return($tasks)
            ->expect(class: Log::class)
            ->return($log);

        $hub->fake("interpret")
            ->expect(entry: "#0");

        $persistence->fake("interpret")
            ->expect(entry: "#1");

        $tasks->fake("interpret")
            ->expect(entry: "#2");

        $log->fake("interpret")
            ->expect(entry: "#3");

        $interpreter->interpret([
            "hub" => "#0",
            "persistence" => "#1",
            "tasks" => "#2",
            "log" => "#3",
            "dir" => null, // valid dirs and done
            "state" => null,
            "cache" => null,
            "config" => null
        ]);
    }

    public function testInvalidWrapperType(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $config = $this->createMock(Config::class);
        $interpreter = new Interpreter($box, $bus);

        $this->expectException(Exception::class);

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

                // done
                throw new Exception;
            });

        $interpreter->interpret(false);
    }

    public function testInvalidEntryKey(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->recycleMock(Bus::class);
        $config = $this->recycleMock(Config::class);
        $interpreter = new Interpreter($box, $bus);

        $this->expectException(Exception::class);

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($config) {
                $this->validate($class)
                    ->as(Config::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                $this->validate($arguments["breadcrumb"])
                    ->as(["###"]);

                return $config;
            });

        $interpreter->interpret(["###" => null]);
    }
}