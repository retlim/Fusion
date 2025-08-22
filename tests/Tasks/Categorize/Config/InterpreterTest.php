<?php
/**
 * Fusion. A package manager for PHP-based projects.
 * Copyright Valvoid
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

namespace Valvoid\Fusion\Tests\Tasks\Categorize\Config;

use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Tasks\Categorize\Categorize;
use Valvoid\Fusion\Tasks\Categorize\Config\Interpreter;
use Valvoid\Fusion\Tests\Tasks\Categorize\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Categorize\Mocks\BusMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Config interpreter test.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class InterpreterTest extends Test
{
    protected string|array $coverage = Interpreter::class;
    private BusMock $bus;

    public function __construct()
    {
        $this->bus = new BusMock;
        $box = new BoxMock;
        $box->bus = $this->bus;

        $this->testReset();
        $this->testInvalidType();
        $this->testDefault();
        $this->testInflated();

        $box::unsetInstance();
    }

    public function testReset(): void
    {
        $this->bus->event = null;

        Interpreter::interpret([], null);

        // assert nothing
        if ($this->bus->event !== null)
            $this->handleFailedTest();
    }

    public function testInvalidType(): void
    {
        $this->bus->event = null;

        // must be string or array
        Interpreter::interpret([], 3455);

        if ($this->bus->event === null ||
            $this->bus->event->getLevel() !== Level::ERROR)
            $this->handleFailedTest();
    }

    public function testDefault(): void
    {
        $this->bus->event = null;

        // default string task config
        Interpreter::interpret([], Categorize::class);

        // assert nothing
        if ($this->bus->event !== null)
            $this->handleFailedTest();
    }

    public function testInflated(): void
    {
        $this->bus->event = null;

        Interpreter::interpret([], [
            "task" => Categorize::class
        ]);

        // assert nothing
        if ($this->bus->event !== null)
            $this->handleFailedTest();
    }
}