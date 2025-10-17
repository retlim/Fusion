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

namespace Valvoid\Fusion\Tests\Config\Interpreter\Log;

use Exception;
use Throwable;
use Valvoid\Fusion\Config\Interpreter\Log as LogInterpreter;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Tests\Config\Interpreter\Log\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Config\Interpreter\Log\Mocks\BusMock;
use Valvoid\Fusion\Tests\Config\Interpreter\Log\Mocks\ConfigEventMock;
use Valvoid\Fusion\Tests\Config\Interpreter\Log\Mocks\ConfigMock;
use Valvoid\Fusion\Tests\Config\Interpreter\Log\Mocks\InterpreterMock;
use Valvoid\Fusion\Tests\Test;

class LogTest extends Test
{
    protected string|array $coverage = LogInterpreter::class;
    private BoxMock $box;

    public function __construct()
    {
        $this->box = new BoxMock;

        $this->testReset();
        $this->testInvalidType();
        $this->testInvalidKey();
        $this->testDefaultSerializer();
        $this->testConfiguredSerializer();
        $this->testAnonymousSerializer();

        $this->box::unsetInstance();
    }

    public function testDefaultSerializer(): void
    {
        try {
            $interpreter = new LogInterpreter(
                box:$this->box,
                config: new ConfigMock,
                bus: new BusMock
            );

            // nothing to interpret
            $interpreter->interpret(["serializers" => [
                "test" => "#0\\#1\\#2"
            ]]);

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testConfiguredSerializer(): void
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

            $interpreter = new LogInterpreter(
                box:$this->box,
                config: $configuration,
                bus: new BusMock
            );

            $interpreter->interpret(["serializers" => [
                "test" => [
                    "serializer" => "#0\\#1\\#2",
                    "whatever"
                ]
            ]]);

            if ($get != ["#0\#1\Config\Interpreter"] ||
                $mock::$breadcrumb != ["log", "serializers", "test", "serializer"] ||
                $mock::$entry != [
                    "serializer" => "#0\\#1\\#2",
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

    public function testAnonymousSerializer(): void
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

            $interpreter = new LogInterpreter(
                box:$this->box,
                config: $configuration,
                bus: new BusMock
            );

            $interpreter->interpret(["serializers" => [
                "test" => [
                    "whatever"
                ]
            ]]);

            if ($get != ["#0\#1\Config\Interpreter"] ||
                $mock::$breadcrumb != ["log", "serializers", "test"] ||
                $mock::$entry != ["whatever"] ||
                $has != ["#0\#1\Config\Interpreter"] ||
                $cGet !=  ["log", "serializers", "test", "serializer"])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testReset(): void
    {
        try {
            $interpreter = new LogInterpreter(
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
            $interpreter = new LogInterpreter(
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
                $interpreter->interpret(["serializers" => 9254]);

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
            $interpreter = new LogInterpreter(
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