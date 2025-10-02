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

namespace Valvoid\Fusion\Tests\Config\Interpreter;

use Exception;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Config as ConfigEvent;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Config\Interpreter\Log as LogInterpreter;
use Valvoid\Fusion\Tests\Config\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Test;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class LogTest extends Test
{
    protected string|array $coverage = LogInterpreter::class;

    /** @var ?ConfigEvent last event */
    private ?ConfigEvent $event = null;

    /** @var bool  */
    private bool $throwException = false;

    public function __construct()
    {
        $boxMock = new BoxMock;

        $this->testReset();
        $this->testInvalidType();
        $this->testInvalidKey();

        $boxMock::unsetInstance();
    }

    public function testReset(): void
    {
        $this->event = null;

        Bus::addReceiver(self::class, $this->handleBusEvent(...), ConfigEvent::class);
        LogInterpreter::interpret(null);

        // assert nothing
        if ($this->event !== null)
            $this->handleFailedTest();

        Bus::removeReceiver(self::class);
    }

    public function testInvalidType(): void
    {
        $this->event = null;
        $this->throwException = true;

        Bus::addReceiver(self::class, $this->handleBusEvent(...), ConfigEvent::class);

        try {
            LogInterpreter::interpret(3455); // must be an array

            $this->result = false;

        } catch (Exception) {
            if ($this->event === null || $this->event->getLevel() !== Level::ERROR)
                $this->handleFailedTest();
        }

        $this->throwException = false;

        Bus::removeReceiver(self::class);
    }

    public function testInvalidKey(): void
    {
        $this->event = null;

        Bus::addReceiver(self::class, $this->handleBusEvent(...), ConfigEvent::class);
        LogInterpreter::interpret(["key" => true]);

        if ($this->event === null || $this->event->getLevel() !== Level::ERROR)
            $this->handleFailedTest();

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