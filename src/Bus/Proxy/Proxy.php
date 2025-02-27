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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Valvoid\Fusion\Bus\Proxy;

use Closure;
use Valvoid\Fusion\Bus\Events\Event;

/**
 * Event bus.
 *
 * @Copyright Valvoid
 * @license GNU GPLv3
 */
interface Proxy
{
    /**
     * Adds event receiver.
     *
     * @param string $id Receiver ID.
     * @param Closure $callback Receiver callback.
     * @param string ...$events Event class name IDs.
     */
    public function addReceiver(string $id, Closure $callback, string ...$events): void;

    /**
     * Sends the event to all receivers.
     *
     * @param Event $event Event.
     */
    public function broadcast(Event $event): void;

    /**
     * Removes selected or complete event receiver.
     *
     * @param string $id Receiver ID.
     * @param string ...$events Event class name IDs.
     */
    public function removeReceiver(string $id, string ...$events): void;
}