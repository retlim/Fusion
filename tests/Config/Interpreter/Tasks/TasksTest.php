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
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Valvoid\Fusion\Tests\Config\Interpreter\Tasks;

use Exception;
use Throwable;
use Valvoid\Fusion\Config\Interpreter\Tasks as TasksInterpreter;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Tests\Config\Interpreter\Tasks\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Config\Interpreter\Tasks\Mocks\BusMock;
use Valvoid\Fusion\Tests\Config\Interpreter\Tasks\Mocks\ConfigEventMock;
use Valvoid\Fusion\Tests\Config\Interpreter\Tasks\Mocks\ConfigMock;
use Valvoid\Fusion\Tests\Config\Interpreter\Tasks\Mocks\InterpreterMock;
use Valvoid\Fusion\Tests\Test;

class TasksTest extends Test
{
    protected string|array $coverage = TasksInterpreter::class;

    private BoxMock $box;

    public function __construct()
    {
        $this->box = new BoxMock;

        $this->testReset();
        $this->testInvalidType();
        $this->testInvalidKey();
        $this->testDefaultTask();
        $this->testGroupedDefaultTask();
        $this->testConfiguredTask();
        $this->testGroupedConfiguredTask();
        $this->testAnonymousTask();
        $this->testGroupedAnonymousTask();

        $this->box::unsetInstance();
    }

    public function testGroupedDefaultTask(): void
    {
        try {
            $configuration = new ConfigMock;
            $get = [];
            $configuration->get = function(...$class) use (&$get) {
                $get = [...$class];

                return false;
            };
            $interpreter = new TasksInterpreter(
                box:$this->box,
                config: $configuration,
                bus: new BusMock
            );

            // nothing to interpret
            $interpreter->interpret(["g" => [
                "test" => "#0\\#1\\#2"
            ]]);

            if ($get != ["tasks", "g", "task"])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testDefaultTask(): void
    {
        try {
            $interpreter = new TasksInterpreter(
                box:$this->box,
                config: new ConfigMock,
                bus: new BusMock
            );

            // nothing to interpret
            $interpreter->interpret([
                "test" => "#0\\#1\\#2"
            ]);

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testGroupedConfiguredTask(): void
    {
        try {
            $configuration = new ConfigMock;
            $has =
            $cGet =
            $get = [];
            $configuration->has = function($class) use (&$has) {
                $has[] = $class;

                return true;
            };

            $configuration->get = function(...$class) use (&$cGet) {
                $cGet = [...$class];

                return false;
            };

            $mock = new InterpreterMock;
            $this->box->get = function ($class) use (&$get, $mock) {
                $get[] = $class;

                return $mock;
            };

            $interpreter = new TasksInterpreter(
                box:$this->box,
                config: $configuration,
                bus: new BusMock
            );

            $interpreter->interpret(["g" => [
                "test" => [
                    "task" => "#0\\#1\\#2",
                    "whatever"
                ]]]);

            if ($get != ["#0\#1\Config\Interpreter"] ||
                $cGet != ["tasks", "g", "task"] ||
                $mock::$breadcrumb != ["tasks", "g", "test"] ||
                $mock::$entry != [
                    "task" => "#0\\#1\\#2",
                    "whatever"] ||
                $has != [
                    "#0\#1\#2",
                    "#0\#1\Config\Interpreter"
                ]) $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testConfiguredTask(): void
    {
        try {
            $configuration = new ConfigMock;
            $has =
            $get = [];
            $configuration->has = function($class) use (&$has) {
                $has[] = $class;

                return true;
            };

            $mock = new InterpreterMock;
            $this->box->get = function ($class) use (&$get, $mock) {
                $get[] = $class;

                return $mock;
            };

            $interpreter = new TasksInterpreter(
                box:$this->box,
                config: $configuration,
                bus: new BusMock
            );

            $interpreter->interpret([
                "test" => [
                    "task" => "#0\\#1\\#2",
                    "whatever"
                ]]);

            if ($get != ["#0\#1\Config\Interpreter"] ||
                $mock::$breadcrumb != ["tasks", "test"] ||
                $mock::$entry != [
                    "task" => "#0\\#1\\#2",
                    "whatever"] ||
                $has != [
                    "#0\#1\#2",
                    "#0\#1\Config\Interpreter"
                ])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testGroupedAnonymousTask(): void
    {
        try {
            $configuration = new ConfigMock;
            $has =
            $cGet =
            $get = [];
            $cGets = 0;
            $configuration->has = function($class) use (&$has) {
                $has[] = $class;

                return true;
            };

            $configuration->get = function(...$class) use (&$cGet, &$cGets) {
                if ($cGets == 0) {
                    $cGets++;

                    return false;
                }
                $cGet = [...$class];

                return "#0\\#1\\#2";
            };

            $mock = new InterpreterMock;
            $this->box->get = function ($class) use (&$get, $mock) {
                $get[] = $class;

                return $mock;
            };

            $interpreter = new TasksInterpreter(
                box:$this->box,
                config: $configuration,
                bus: new BusMock
            );

            $interpreter->interpret(["g" => [
                "test" => [
                    "whatever"
                ]
            ]]);

            if ($get != ["#0\#1\Config\Interpreter"] ||
                $mock::$breadcrumb != ["tasks", "g", "test"] ||
                $mock::$entry != ["whatever"] ||
                $has != ["#0\#1\Config\Interpreter"] ||
                $cGet !=  ["tasks", "g", "test", "task"])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testAnonymousTask(): void
    {
        try {
            $configuration = new ConfigMock;
            $has =
            $cGet =
            $get = [];
            $configuration->has = function($class) use (&$has) {
                $has[] = $class;

                return true;
            };

            $configuration->get = function(...$class) use (&$cGet) {
                $cGet = [...$class];

                return "#0\\#1\\#2";
            };

            $mock = new InterpreterMock;
            $this->box->get = function ($class) use (&$get, $mock) {
                $get[] = $class;

                return $mock;
            };

            $interpreter = new TasksInterpreter(
                box:$this->box,
                config: $configuration,
                bus: new BusMock
            );

            $interpreter->interpret([
                "test" => [
                    "whatever"
                ]
            ]);

            if ($get != ["#0\#1\Config\Interpreter"] ||
                $mock::$breadcrumb != ["tasks", "test"] ||
                $mock::$entry != ["whatever"] ||
                $has != ["#0\#1\Config\Interpreter"] ||
                $cGet !=  ["tasks", "test", "task"])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testReset(): void
    {
        try {
            $interpreter = new TasksInterpreter(
                box:$this->box,
                config: new ConfigMock,
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
            $interpreter = new TasksInterpreter(
                box:$this->box,
                config: new ConfigMock,
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

                // must be an array
                $interpreter->interpret(["test" => 9254]);

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
            $interpreter = new TasksInterpreter(
                box: $this->box,
                config: new ConfigMock,
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

                $interpreter->interpret(["test-" => "#"]);

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