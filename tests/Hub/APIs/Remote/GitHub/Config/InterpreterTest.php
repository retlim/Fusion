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

namespace Valvoid\Fusion\Tests\Hub\APIs\Remote\GitHub\Config;

use Valvoid\Fusion\Hub\APIs\Remote\GitHub\Config\Interpreter;
use Valvoid\Fusion\Hub\APIs\Remote\GitHub\GitHub;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Tests\Hub\APIs\Remote\GitHub\Config\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Hub\APIs\Remote\GitHub\Config\Mocks\BusMock;
use Valvoid\Fusion\Tests\Test;

class InterpreterTest extends Test
{
    /** @var string|array Code coverage. */
    protected string|array $coverage = Interpreter::class;

    private BoxMock $container;

    public function __construct()
    {
        $this->container = new BoxMock;
        $this->container->bus = new BusMock;

        $this->testReset();
        $this->testInvalid();
        $this->testDefault();
        $this->testCustom();

        $this->container::unsetInstance();
    }

    public function testCustom(): void
    {
        $this->container->bus->event = null;

        Interpreter::interpret([], [
            "api" => GitHub::class,
            "protocol" => "http",
            "domain" => "github.com",
            "tokens" => [
                "token"
            ]
        ]);

        if ($this->container->bus->event !== null)
            $this->handleFailedTest();
    }

    public function testDefault(): void
    {
        $this->container->bus->event = null;

        Interpreter::interpret([], GitHub::class);

        if ($this->container->bus->event !== null)
            $this->handleFailedTest();
    }

    public function testInvalid(): void
    {
        $this->container->bus->event = null;

        Interpreter::interpret([], 34);

        if ($this->container->bus->event === null ||
            $this->container->bus->event->getLevel() !== Level::ERROR)
            $this->handleFailedTest();
    }

    public function testReset(): void
    {
        $this->container->bus->event = null;

        Interpreter::interpret([], null);

        if ($this->container->bus->event !== null)
            $this->handleFailedTest();
    }
}