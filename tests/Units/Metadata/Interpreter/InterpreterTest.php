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

namespace Valvoid\Fusion\Tests\Units\Metadata\Interpreter;

use Exception;
use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Metadata;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Metadata\Interpreter\Interpreter;
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

        $interpreter->interpret("", [
            "name" => null,
            "description" => null,
            "version" => null
        ]);
    }

    public function testInvalidType(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $metadata = $this->createMock(Metadata::class);
        $interpreter = new Interpreter(
            box: $box,
            bus: $bus
        );

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($metadata) {
                $this->validate($class)
                    ->as(Metadata::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                return $metadata;
            });

        $bus->fake("broadcast")
            ->hook(function ($event) use ($metadata) {
                $this->validate($event)
                    ->as($metadata);

                throw new Exception;
            });

        $this->expectException(Exception::class);
        $interpreter->interpret("", 623);
    }

    public function testInvalidKey(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $metadata = $this->createMock(Metadata::class);
        $interpreter = new Interpreter(
            box: $box,
            bus: $bus
        );

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($metadata) {
                $this->validate($class)
                    ->as(Metadata::class);

                $this->validate($arguments["level"])
                    ->as(Level::NOTICE);

                $this->validate($arguments["breadcrumb"])
                    ->as(["key"]);

                return $metadata;
            });

        $bus->fake("broadcast")
            ->hook(function ($event) use ($metadata) {
                $this->validate($event)
                    ->as($metadata);
            });

        $interpreter->interpret("", ["key" => true]);
    }
}