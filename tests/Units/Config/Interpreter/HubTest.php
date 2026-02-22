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
use Valvoid\Fusion\Bus\Events\Config as ConfigEvent;
use Valvoid\Fusion\Config\Config;
use Valvoid\Fusion\Config\Interpreter;
use Valvoid\Fusion\Config\Interpreter\Hub;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Reflex\Test\Wrapper;

class HubTest extends Wrapper
{
    public function testReset(): void
    {
        $box = $this->createStub(Box::class);
        $config = $this->createStub(Config::class);
        $bus = $this->createStub(Bus::class);
        $hub = new Hub($box, $config, $bus);

        $hub->interpret(null);
    }

    public function testInvalidHubType(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $config = $this->createStub(Config::class);
        $configEvent = $this->createStub(ConfigEvent::class);
        $hub = new Hub($box, $config, $bus);

        $this->expectException(Exception::class);

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($configEvent) {
                $this->validate($class)
                    ->as(ConfigEvent::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                $this->validate($arguments["breadcrumb"])
                    ->as(["hub"]);

                return $configEvent;
            });

        $bus->fake("broadcast")
            ->hook(function ($event) use ($configEvent) {
                $this->validate($event)
                    ->as($configEvent);

                // done
                throw new Exception;
            });

        $hub->interpret(222);
    }

    public function testUnknownHubEntryKey(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $config = $this->createStub(Config::class);
        $configEvent = $this->createStub(ConfigEvent::class);
        $hub = new Hub($box, $config, $bus);

        $this->expectException(Exception::class);

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($configEvent) {
                $this->validate($class)
                    ->as(ConfigEvent::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                $this->validate($arguments["breadcrumb"])
                    ->as(["hub", "###"]);

                return $configEvent;
            });

        $bus->fake("broadcast")
            ->hook(function ($event) use ($configEvent) {
                $this->validate($event)
                    ->as($configEvent);

                // done
                throw new Exception;
            });

        $hub->interpret(["###" => "#"]);
    }

    public function testInvalidHubApisType(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $config = $this->createStub(Config::class);
        $configEvent = $this->createStub(ConfigEvent::class);
        $hub = new Hub($box, $config, $bus);

        $this->expectException(Exception::class);

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($configEvent) {
                $this->validate($class)
                    ->as(ConfigEvent::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                $this->validate($arguments["breadcrumb"])
                    ->as(["hub", "apis"]);

                return $configEvent;
            });

        $bus->fake("broadcast")
            ->hook(function ($event) use ($configEvent) {
                $this->validate($event)
                    ->as($configEvent);

                // done
                throw new Exception;
            });

        $hub->interpret(["apis" => 222]);
    }

    public function testDefaultApi(): void
    {
        $box = $this->createStub(Box::class);
        $config = $this->createStub(Config::class);
        $bus = $this->createStub(Bus::class);
        $hub = new Hub($box, $config, $bus);

        $hub->interpret(["apis" => [
            "test" => "#0\\#1\\#2" // class without config
        ]]);
    }

    public function testConfiguredApi(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createStub(Bus::class);
        $config = $this->createMock(Config::class);
        $interpreter = $this->createMock(Interpreter::class);
        $hub = new Hub($box, $config, $bus);

        $config->fake("hasLazy")
            ->expect(class: "#0\\#1")
            ->return(true)
            ->expect(class: "#0\\Config\\Interpreter");

        $box->fake("get")
            ->expect(class: "#0\\Config\\Interpreter")
            ->return($interpreter);

        $interpreter->fake("interpret")
            ->expect(breadcrumb: ["hub", "apis", "test"],
                entry: [
                    "api" => "#0\\#1",
                    "whatever"
                ]);

        $hub->interpret(["apis" => [
            "test" => [
                "api" => "#0\\#1", // reference class
                "whatever"
            ]]]);
    }

    public function testAnonymousApi(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createStub(Bus::class);
        $config = $this->createMock(Config::class);
        $interpreter = $this->createMock(Interpreter::class);
        $hub = new Hub($box, $config, $bus);

        $config->fake("get")
            ->expect(breadcrumb: ["hub", "apis", "test", "api"])
            ->return("#0\\#1")
            ->fake("hasLazy")
            ->expect(class: "#0\\Config\\Interpreter")
            ->return(true);

        $box->fake("get")
            ->expect(class: "#0\\Config\\Interpreter")
            ->return($interpreter);

        $interpreter->fake("interpret")
            ->expect(breadcrumb: ["hub", "apis", "test"],
                entry: ["whatever"]);

        $hub->interpret(["apis" => [
            "test" => [
                // "api" => "#0\\#1", // no reference class
                "whatever"
            ]]]);
    }
}