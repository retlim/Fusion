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

namespace Valvoid\Fusion\Tests\Hub\APIs\Remote\Valvoid\Config;

use Valvoid\Fusion\Hub\APIs\Remote\Valvoid\Config\Interpreter;
use Valvoid\Fusion\Hub\APIs\Remote\Valvoid\Valvoid;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Tests\Hub\APIs\Remote\Valvoid\Config\Mocks\ContainerMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Config interpreter test.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class InterpreterTest extends Test
{
    /** @var string|array Code coverage. */
    protected string|array $coverage = Interpreter::class;

    private ContainerMock $container;

    public function __construct()
    {
        $this->container = new ContainerMock;

        $this->testReset();
        $this->testInvalid();
        $this->testDefault();
        $this->testCustom();

        $this->container->destroy();
    }

    public function testCustom(): void
    {
        $this->container->logic->bus->event = null;

        Interpreter::interpret([], [
            "api" => Valvoid::class,
            "protocol" => "http",
            "domain" => "valvoid.com",
            "tokens" => [
                "token"
            ]
        ]);

        if ($this->container->logic->bus->event !== null) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testDefault(): void
    {
        $this->container->logic->bus->event = null;

        Interpreter::interpret([], Valvoid::class);

        if ($this->container->logic->bus->event !== null) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testInvalid(): void
    {
        $this->container->logic->bus->event = null;

        Interpreter::interpret([], 34);

        if ($this->container->logic->bus->event === null ||
            $this->container->logic->bus->event->getLevel() !== Level::ERROR) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testReset(): void
    {
        $this->container->logic->bus->event = null;

        Interpreter::interpret([], null);

        if ($this->container->logic->bus->event !== null) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}