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

namespace Valvoid\Fusion\Tests\Hub\APIs\Local\Git\Config;

use Valvoid\Fusion\Hub\APIs\Local\Git\Config\Interpreter;
use Valvoid\Fusion\Hub\APIs\Local\Git\Git;
use Valvoid\Fusion\Tests\Hub\APIs\Local\Git\Config\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Hub\APIs\Local\Git\Config\Mocks\BusMock;
use Valvoid\Fusion\Tests\Test;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class InterpreterTest extends Test
{
    protected string|array $coverage = Interpreter::class;
    protected BoxMock $container;

    public function __construct()
    {
        $this->container = new BoxMock;
        $this->container->bus = new BusMock;

        $this->testDefault();
        $this->testCustom();
        $this->testError();

        $this->container::unsetInstance();
    }

    public function testDefault(): void
    {
        $this->container->bus->event = false;

        Interpreter::interpret([], Git::class);

        if ($this->container->bus->event !== false)
            $this->handleFailedTest();
    }

    public function testCustom(): void
    {
        $this->container->bus->event = false;

        Interpreter::interpret([], [
            "api" => Git::class
        ]);

        if ($this->container->bus->event !== false)
            $this->handleFailedTest();
    }

    public function testError(): void
    {
        $this->container->bus->event = false;

        Interpreter::interpret([], 34);

        if ($this->container->bus->event === false)
            $this->handleFailedTest();
    }
}