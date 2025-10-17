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

namespace Valvoid\Fusion\Tests\Bus;

use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Cache;
use Valvoid\Fusion\Bus\Events\Config;
use Valvoid\Fusion\Bus\Events\Metadata;
use Valvoid\Fusion\Bus\Events\Root;
use Valvoid\Fusion\Bus\Logic;
use Valvoid\Fusion\Tests\Bus\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Bus\Mocks\EventMock;
use Valvoid\Fusion\Tests\Test;

class BusTest extends Test
{
    protected string|array $coverage = [
        Bus::class,
        Logic::class,

        // ballast
        Cache::class,
        Config::class,
        Metadata::class,
        Root::class
    ];

    private BoxMock $box;
    private Logic $logic;
    private EventMock $eventMock;

    public function __construct()
    {
        $this->box = new BoxMock;
        $this->logic = new Logic;
        $this->eventMock = new EventMock;

        // static
        $this->testStaticInterface();

        // logic
        $this->testReceivers();
        $this->testEvents();

        $this->box::unsetInstance();
    }

    public function testReceivers(): void
    {
        $indicator1 = false;
        $indicator2 = false;

        $this->logic->addReceiver(1, function () use (&$indicator1) {
            $indicator1 = true;

        },  $this->eventMock::class);

        $this->logic->addReceiver(2, function () use (&$indicator2) {
            $indicator2 = true;

        },  $this->eventMock::class);

        $this->logic->broadcast($this->eventMock);

        if (!$indicator1 || !$indicator2)
            $this->handleFailedTest();
    }

    public function testEvents(): void
    {
        $indicator1 = false;
        $indicator2 = false;

        $this->logic->addReceiver(1, function () use (&$indicator1) {
            $indicator1 = true;

        },  $this->eventMock::class);

        $this->logic->addReceiver(2, function () use (&$indicator2) {
            $indicator2 = true;

        }); // no event as pseudo diff type

        $this->logic->broadcast($this->eventMock);

        if (!$indicator1 || $indicator2)
            $this->handleFailedTest();
    }

    public function testStaticInterface(): void
    {
        Bus::addReceiver(1, function () {});
        Bus::removeReceiver(1);
        Bus::broadcast($this->eventMock);

        // static functions connected to same non-static functions
        if ($this->box->bus->calls !== [
            "addReceiver",
            "removeReceiver",
            "broadcast"])
            $this->handleFailedTest();
    }
}