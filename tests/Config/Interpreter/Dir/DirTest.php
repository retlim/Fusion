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

namespace Valvoid\Fusion\Tests\Config\Interpreter\Dir;

use Exception;
use Throwable;
use Valvoid\Fusion\Bus\Events\Config as ConfigEvent;
use Valvoid\Fusion\Config\Interpreter\Dir as DirInterpreter;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Tests\Config\Interpreter\Dir\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Config\Interpreter\Dir\Mocks\BusMock;
use Valvoid\Fusion\Tests\Config\Interpreter\Dir\Mocks\ConfigEventMock;
use Valvoid\Fusion\Tests\Config\Interpreter\Dir\Mocks\DirParserMock;
use Valvoid\Fusion\Tests\Test;

class DirTest extends Test
{
    protected string|array $coverage = DirInterpreter::class;
    private BoxMock $box;

    public function __construct()
    {
        $this->box = new BoxMock;

        $this->testRootPath();
        $this->testNestedPathError();
        $this->testInvalidTypeError();
        $this->testInvalidKeyError();

        $this->box::unsetInstance();
    }

    public function testRootPath(): void
    {
        try {
            $interpreter = new DirInterpreter($this->box, new BusMock);
            $parser = new DirParserMock;
            $this->box->get = fn () => $parser;
            $parser->path = function () {

                // equal assertion
                return "/#";
            };

            $interpreter->interpret(["dir" => [

                // equal assertion
                "path" => "/#",
                "creatable" => false,
                "clearable" => false
            ]]);

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testNestedPathError(): void
    {
        try {
            $bus = new BusMock;
            $interpreter = new DirInterpreter($this->box, $bus);
            $parser = new DirParserMock;
            $broadcast =
            $event = [];
            $this->box->get = function ($class, ...$args) use ($parser, &$event) {
                if ($class == ConfigEvent::class) {
                    $mock = new ConfigEventMock(...$args);
                    $event[] = $mock;

                    return $mock;
                }

                return $parser;
            };

            $parser->path = function () {

                // not equal assertion trigger
                return "/#";
            };

            try {
                $bus->broadcast = function ($event) use (&$broadcast) {
                    $broadcast[] = $event;

                    throw new Exception;
                };

                $interpreter->interpret(["dir" => [

                    // not equal assertion trigger
                    "path" => "/#/nested",
                    "creatable" => false,
                    "clearable" => false
                ]]);

            } catch (Exception) {}

            if (sizeof($event) != 1 ||
                $broadcast !== $event ||
                $event[0]->level !== Level::ERROR)
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testInvalidTypeError(): void
    {
        try {
            $bus = new BusMock;
            $interpreter = new DirInterpreter($this->box, $bus);
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
                $interpreter->interpret(["dir" => 9254]);

            } catch (Exception) {}

            if (sizeof($event) != 1 ||
                $broadcast !== $event ||
                $event[0]->level !== Level::ERROR)
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testInvalidKeyError(): void
    {
        try {
            $bus = new BusMock;
            $interpreter = new DirInterpreter($this->box, $bus);
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

                $interpreter->interpret(["dir" => [

                    // unknown
                    "key" => ""
                ]]);

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