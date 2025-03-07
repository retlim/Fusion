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

namespace Valvoid\Fusion\Tests\Tasks\Build\Config;

use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Config as ConfigEvent;
use Valvoid\Fusion\Container\Proxy\Logic;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Tasks\Build\Build;
use Valvoid\Fusion\Tasks\Build\Config\Interpreter;
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

    /** @var ?ConfigEvent last event */
    private ?ConfigEvent $event = null;

    public function __construct()
    {
        (new Logic)->get(Bus::class);

        $this->testReset();
        $this->testInvalidType();
        $this->testDefault();
        $this->testInflated();

        (new Logic)->unset(Bus::class);
    }

    public function testReset(): void
    {
        $this->event = null;

        Bus::addReceiver(self::class, $this->handleBusEvent(...), ConfigEvent::class);
        Interpreter::interpret([], null);

        // assert nothing
        if ($this->event !== null) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }

        Bus::removeReceiver(self::class);
    }

    public function testInvalidType(): void
    {
        $this->event = null;

        Bus::addReceiver(self::class, $this->handleBusEvent(...), ConfigEvent::class);
        Interpreter::interpret([], 3455); // must be string or array

        if ($this->event === null || $this->event->getLevel() !== Level::ERROR) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }

        Bus::removeReceiver(self::class);
    }

    public function testDefault(): void
    {
        $this->event = null;

        Bus::addReceiver(self::class, $this->handleBusEvent(...), ConfigEvent::class);
        Interpreter::interpret([], Build::class); // default string task config

        // assert nothing
        if ($this->event !== null) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }

        Bus::removeReceiver(self::class);
    }

    public function testInflated(): void
    {
        $this->event = null;

        Bus::addReceiver(self::class, $this->handleBusEvent(...), ConfigEvent::class);
        Interpreter::interpret([], [
            "task" => Build::class

        ]);

        // assert nothing
        if ($this->event !== null) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }

        Bus::removeReceiver(self::class);
    }

    /**
     * Handles bus event.
     *
     * @param ConfigEvent $event Root event.
     */
    private function handleBusEvent(ConfigEvent $event): void
    {
        $this->event = $event;
    }
}