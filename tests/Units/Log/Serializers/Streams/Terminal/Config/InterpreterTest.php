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

namespace Valvoid\Fusion\Tests\Units\Log\Serializers\Streams\Terminal\Config;

use Exception;
use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Config;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Log\Serializers\Streams\Terminal\Config\Interpreter;
use Valvoid\Fusion\Log\Serializers\Streams\Terminal\Terminal;
use Valvoid\Reflex\Test\Wrapper;

class InterpreterTest extends Wrapper
{
    public function testDefaultSerializer(): void
    {
        $box = $this->createStub(Box::class);
        $bus = $this->createStub(Bus::class);
        $interpreter = new Interpreter(
            box: $box,
            bus: $bus
        );

        $interpreter->interpret([], Terminal::class);
    }

    public function testWrapperReset(): void
    {
        $box = $this->createStub(Box::class);
        $bus = $this->createStub(Bus::class);
        $interpreter = new Interpreter(
            box: $box,
            bus: $bus
        );

        $interpreter->interpret([], null);
    }

    public function testWrapperException(): void
    {
        $box = $this->createStub(Box::class);
        $bus = $this->createStub(Bus::class);
        $config = $this->createStub(Config::class);
        $interpreter = new Interpreter(
            box: $box,
            bus: $bus
        );

        $this->expectException(Exception::class);

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($config) {
                $this->validate($class)
                    ->as(Config::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                $this->validate($arguments["breadcrumb"])
                    ->as(["#1"]);

                return $config;
            });

        $bus->fake("broadcast")
            ->hook(function ($event) use ($config) {
                $this->validate($event)
                    ->as($config);

                // done
                throw new Exception;
            });

        $interpreter->interpret(["#1"], 111);
    }

    public function testWrapperKeyException(): void
    {
        $box = $this->createStub(Box::class);
        $bus = $this->createStub(Bus::class);
        $config = $this->createStub(Config::class);
        $interpreter = new Interpreter(
            box: $box,
            bus: $bus
        );

        $this->expectException(Exception::class);

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($config) {
                $this->validate($class)
                    ->as(Config::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                $this->validate($arguments["breadcrumb"])
                    ->as(["#1", "###"]);

                return $config;
            });

        $bus->fake("broadcast")
            ->hook(function ($event) use ($config) {
                $this->validate($event)
                    ->as($config);

                // done
                throw new Exception;
            });

        $interpreter->interpret(["#1"], ["###" => "###"]);
    }

    public function testDefaultSerializerException(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $config = $this->createStub(Config::class);
        $interpreter = new Interpreter(
            box: $box,
            bus: $bus
        );

        $this->expectException(Exception::class);

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($config) {
                $this->validate($class)
                    ->as(Config::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                $this->validate($arguments["breadcrumb"])
                    ->as(["#1"]);

                return $config;
            });

        $bus->fake("broadcast")
            ->hook(function ($event) use ($config) {
                $this->validate($event)
                    ->as($config);

                // done
                throw new Exception;
            });

        $interpreter->interpret(["#1"], "#");
    }

    public function testConfiguredSerializer(): void
    {
        $box = $this->createStub(Box::class);
        $bus = $this->createStub(Bus::class);
        $interpreter = new Interpreter(
            box: $box,
            bus: $bus
        );

        $interpreter->interpret([], ["serializer" => Terminal::class]);
    }

    public function testConfiguredSerializerException(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $config = $this->createStub(Config::class);
        $interpreter = new Interpreter(
            box: $box,
            bus: $bus
        );

        $this->expectException(Exception::class);

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($config) {
                $this->validate($class)
                    ->as(Config::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                $this->validate($arguments["breadcrumb"])
                    ->as(["#1", "serializer"]);

                return $config;
            });

        $bus->fake("broadcast")
            ->hook(function ($event) use ($config) {
                $this->validate($event)
                    ->as($config);

                // done
                throw new Exception;
            });

        $interpreter->interpret(["#1"], ["serializer" => "###"]);
    }

    public function testThreshold(): void
    {
        $box = $this->createStub(Box::class);
        $bus = $this->createStub(Bus::class);
        $interpreter = new Interpreter(
            box: $box,
            bus: $bus
        );

        $interpreter->interpret([], ["threshold" => Level::INFO]);
    }

    public function testThresholdException(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $config = $this->createStub(Config::class);
        $interpreter = new Interpreter(
            box: $box,
            bus: $bus
        );

        $this->expectException(Exception::class);

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($config) {
                $this->validate($class)
                    ->as(Config::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                $this->validate($arguments["breadcrumb"])
                    ->as(["#1", "threshold"]);

                return $config;
            });

        $bus->fake("broadcast")
            ->hook(function ($event) use ($config) {
                $this->validate($event)
                    ->as($config);

                // done
                throw new Exception;
            });

        $interpreter->interpret(["#1"], ["threshold" => "###"]);
    }
}