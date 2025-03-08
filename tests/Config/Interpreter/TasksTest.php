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

namespace Valvoid\Fusion\Tests\Config\Interpreter;

use Exception;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Config as ConfigEvent;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Config\Interpreter\Tasks as TasksInterpreter;
use Valvoid\Fusion\Tests\Config\Mocks\ContainerMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Config interpreter test.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class TasksTest extends Test
{
    protected string|array $coverage = TasksInterpreter::class;

    /** @var ?ConfigEvent last event */
    private ?ConfigEvent $event = null;

    /** @var bool  */
    private bool $throwException = false;

    public function __construct()
    {
        $containerMock = new ContainerMock;

        $this->testReset();
        $this->testInvalidType();
        $this->testInvalidKey();

        $containerMock->destroy();
    }

    public function testReset(): void
    {
        $this->event = null;

        Bus::addReceiver(self::class, $this->handleBusEvent(...), ConfigEvent::class);
        TasksInterpreter::interpret(null);

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
        $this->throwException = true;

        Bus::addReceiver(self::class, $this->handleBusEvent(...), ConfigEvent::class);

        try {
            TasksInterpreter::interpret(3455); // must be an array

            $this->result = false;

        } catch (Exception) {
            if ($this->event === null || $this->event->getLevel() !== Level::ERROR) {
                echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

                $this->result = false;
            }
        }

        $this->throwException = false;

        Bus::removeReceiver(self::class);
    }

    public function testInvalidKey(): void
    {
        $this->event = null;

        Bus::addReceiver(self::class, $this->handleBusEvent(...), ConfigEvent::class);
        TasksInterpreter::interpret(["key" => true]);

        if ($this->event === null || $this->event->getLevel() !== Level::ERROR) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }

        Bus::removeReceiver(self::class);
    }

    /**
     * Handles bus event.
     *
     * @param ConfigEvent $event Root event.
     * @throws Exception
     */
    private function handleBusEvent(ConfigEvent $event): void
    {
        $this->event = $event;

        if ($this->throwException)
            throw new Exception;
    }
}