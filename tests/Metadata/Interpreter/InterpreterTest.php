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

namespace Valvoid\Fusion\Tests\Metadata\Interpreter;

use Exception;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Metadata as MetadataEvent;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Metadata\Interpreter\Interpreter;
use Valvoid\Fusion\Tests\Metadata\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Test;

class InterpreterTest extends Test
{
    protected string|array $coverage = Interpreter::class;

    /** @var ?MetadataEvent last event */
    private ?MetadataEvent $event = null;

    /** @var bool  */
    private bool $throwException = false;

    public function __construct()
    {
        $containerMock = new BoxMock;

        $this->testReset();
        $this->testInvalidType();
        $this->testInvalidKey();

        $containerMock::unsetInstance();
    }

    public function testReset(): void
    {
        $this->event = null;

        Bus::addReceiver(self::class, $this->handleBusEvent(...), MetadataEvent::class);
        Interpreter::interpret("", [
            "name" => null,
            "description" => null,
            "version" => null
        ]);

        // assert nothing
        if ($this->event !== null)
            $this->handleFailedTest();

        Bus::removeReceiver(self::class);
    }

    public function testInvalidType(): void
    {
        $this->event = null;
        $this->throwException = true;

        Bus::addReceiver(self::class, $this->handleBusEvent(...), MetadataEvent::class);

        try {
            Interpreter::interpret("",623); // must be an array

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

        Bus::addReceiver(self::class, $this->handleBusEvent(...), MetadataEvent::class);
        Interpreter::interpret("", ["key" => true]);

        if ($this->event === null || $this->event->getLevel() !== Level::NOTICE)
            $this->handleFailedTest();

        Bus::removeReceiver(self::class);
    }

    /**
     * Handles bus event.
     *
     * @param MetadataEvent $event Root event.
     * @throws Exception
     */
    private function handleBusEvent(MetadataEvent $event): void
    {
        $this->event = $event;

        if ($this->throwException)
            throw new Exception;
    }
}