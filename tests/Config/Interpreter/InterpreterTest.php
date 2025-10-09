<?php
/**
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
 */

namespace Valvoid\Fusion\Tests\Config\Interpreter;

use Exception;
use Throwable;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Config\Interpreter\Interpreter;
use Valvoid\Fusion\Tests\Config\Interpreter\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Config\Interpreter\Mocks\BusMock;
use Valvoid\Fusion\Tests\Config\Interpreter\Mocks\ConfigEventMock;
use Valvoid\Fusion\Tests\Test;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class InterpreterTest extends Test
{
    protected string|array $coverage = Interpreter::class;
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
            $bus = new BusMock;
            $interpreter = new Interpreter(
                box: $this->box,
                bus: $bus
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
            $interpreter = new Interpreter(
                box: $this->box,
                bus: $bus
            );

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

                // must be an array
                $interpreter->interpret(3455);

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
            $interpreter = new Interpreter(
                box: $this->box,
                bus: $bus
            );

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

                // unknown key
                $interpreter->interpret(["key" => true]);

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