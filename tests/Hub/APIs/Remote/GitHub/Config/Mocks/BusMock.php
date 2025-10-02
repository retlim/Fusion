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

namespace Valvoid\Fusion\Tests\Hub\APIs\Remote\GitHub\Config\Mocks;

use Closure;
use Valvoid\Fusion\Bus\Events\Event;
use Valvoid\Fusion\Bus\Proxy;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class BusMock implements Proxy
{
    // last event
    public ?Event $event = null;

    public function broadcast(Event $event): void
    {
        $this->event = $event;
    }

    public function addReceiver(string $id, Closure $callback, string ...$events): void {}
    public function removeReceiver(string $id, string ...$events): void {}
}