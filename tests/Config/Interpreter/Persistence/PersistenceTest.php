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

namespace Valvoid\Fusion\Tests\Config\Interpreter\Persistence;

use Exception;
use Throwable;
use Valvoid\Fusion\Config\Interpreter\Persistence as PersistenceInterpreter;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Tests\Config\Interpreter\Persistence\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Config\Interpreter\Persistence\Mocks\BusMock;
use Valvoid\Fusion\Tests\Config\Interpreter\Persistence\Mocks\ConfigEventMock;
use Valvoid\Fusion\Tests\Test;

class PersistenceTest extends Test
{
    protected string|array $coverage = PersistenceInterpreter::class;

    private BoxMock $box;

    public function __construct()
    {
        $this->box = new BoxMock;

        $this->testReset();
        $this->testInvalidType();
        $this->testInvalidKey();

        $this->box::unsetInstance();
    }


    public function testReset(): void
    {
        try {
            $interpreter = new PersistenceInterpreter(
                box:$this->box,
                bus: new BusMock
            );

            $interpreter->interpret(null);

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testInvalidType(): void
    {
        try {
            $bus = new BusMock;
            $interpreter = new PersistenceInterpreter(
                box:$this->box,
                bus: $bus);
            $broadcast =
            $event = [];
            $this->box->get = function ($class, ...$args) use (&$event) {
                $mock = new ConfigEventMock(...$args);
                $event[] = $mock;

                return $mock;
            };

            try {
                $bus->broadcast = function ($event) use (&$broadcast) {
                    $broadcast[] = $event;

                    throw new Exception;
                };

                $interpreter->interpret(["overlay" => 9254]);

            } catch (Exception) {}

            if (sizeof($event) != 1 ||
                $broadcast !== $event ||
                $event[0]->level !== Level::ERROR)
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testInvalidKey(): void
    {
        try {
            $bus = new BusMock;
            $interpreter = new PersistenceInterpreter(
                box:$this->box,
                bus: $bus);
            $broadcast =
            $event = [];
            $this->box->get = function ($class, ...$args) use (&$event) {
                $mock = new ConfigEventMock(...$args);
                $event[] = $mock;

                return $mock;
            };

            try {
                $bus->broadcast = function ($event) use (&$broadcast) {
                    $broadcast[] = $event;

                    throw new Exception;
                };

                $interpreter->interpret(["key" => ""]);

            } catch (Exception) {}

            if (sizeof($event) != 1 ||
                $broadcast !== $event ||
                $event[0]->level !== Level::ERROR)
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }
}