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

namespace Valvoid\Fusion\Tests\Hub\APIs\Local\Dir\Config;

use Valvoid\Fusion\Hub\APIs\Local\Dir\Config\Interpreter;
use Valvoid\Fusion\Hub\APIs\Local\Dir\Dir;
use Valvoid\Fusion\Tests\Hub\APIs\Local\Dir\Config\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Hub\APIs\Local\Dir\Config\Mocks\BusMock;
use Valvoid\Fusion\Tests\Test;

class InterpreterTest extends Test
{
    protected string|array $coverage = Interpreter::class;
    protected BoxMock $boxMock;

    public function __construct()
    {
        $this->boxMock = new BoxMock;
        $this->boxMock->bus = new BusMock;

        $this->testDefault();
        $this->testCustom();
        $this->testError();

        $this->boxMock::unsetInstance();
    }

    public function testDefault(): void
    {
        $this->boxMock->bus->event = false;

        Interpreter::interpret([], Dir::class);

        if ($this->boxMock->bus->event !== false)
            $this->handleFailedTest();
    }

    public function testCustom(): void
    {
        $this->boxMock->bus->event = false;

        Interpreter::interpret([], [
            "api" => Dir::class
        ]);

        if ($this->boxMock->bus->event !== false)
            $this->handleFailedTest();
    }

    public function testError(): void
    {
        $this->boxMock->bus->event = false;

        Interpreter::interpret([], 34);

        if ($this->boxMock->bus->event === false)
            $this->handleFailedTest();
    }
}