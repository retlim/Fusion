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
use Valvoid\Fusion\Config\Interpreter\Persistence;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Reflex\Test\Wrapper;

class PersistenceTest extends Wrapper
{
    public function testReset(): void
    {
        $box = $this->createStub(Box::class);
        $bus = $this->createStub(Bus::class);
        $hub = new Persistence($box, $bus);

        $hub->interpret(null);
    }

    public function testInvalidWrapperType(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $config = $this->createStub(Config::class);
        $persistence = new Persistence($box, $bus);

        $this->expectException(Exception::class);

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($config) {
                $this->validate($class)
                    ->as(Config::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                $this->validate($arguments["breadcrumb"])
                    ->as(["persistence"]);

                return $config;
            });

        $bus->fake("broadcast")
            ->hook(function ($event) use ($config) {
                $this->validate($event)
                    ->as($config);

                // done
                throw new Exception;
            });

        $persistence->interpret(222);
    }

    public function testUnknownEntryKeyError(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $config = $this->createStub(Config::class);
        $persistence = new Persistence($box, $bus);

        $this->expectException(Exception::class);

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($config) {
                $this->validate($class)
                    ->as(Config::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                $this->validate($arguments["breadcrumb"])
                    ->as(["persistence", "###"]);

                return $config;
            });

        $bus->fake("broadcast")
            ->hook(function ($event) use ($config) {
                $this->validate($event)
                    ->as($config);

                // done
                throw new Exception;
            });

        $persistence->interpret(["###" => "##"]);
    }

    public function testOverlayFlagError(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $config = $this->createStub(Config::class);
        $persistence = new Persistence($box, $bus);

        $this->expectException(Exception::class);

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($config) {
                $this->validate($class)
                    ->as(Config::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                $this->validate($arguments["breadcrumb"])
                    ->as(["persistence", "overlay"]);

                return $config;
            });

        $bus->fake("broadcast")
            ->hook(function ($event) use ($config) {
                $this->validate($event)
                    ->as($config);

                // done
                throw new Exception;
            });

        $persistence->interpret(["overlay" => "##"]);
    }
}