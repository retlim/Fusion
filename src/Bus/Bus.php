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

namespace Valvoid\Fusion\Bus;

use Closure;
use Valvoid\Fusion\Bus\Events\Event;
use Valvoid\Fusion\Bus\Proxy\Proxy;
use Valvoid\Fusion\Container\Container;
use Valvoid\Fusion\Log\Events\Errors\Error;

/**
 * Static event bus proxy.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Bus
{
    /**
     * Adds event receiver.
     *
     * @param string $id Receiver ID.
     * @param Closure $callback Receiver callback.
     * @param string ...$events Event class name IDs.
     * @throws Error Internal error.
     */
    public static function addReceiver(string $id, Closure $callback, string ...$events): void
    {
        Container::get(Proxy::class)
            ->addReceiver($id, $callback, ...$events);
    }

    /**
     * Sends the event to all receivers.
     *
     * @param Event $event Event.
     * @throws Error Internal error.
     */
    public static function broadcast(Event $event): void
    {
        Container::get(Proxy::class)
            ->broadcast($event);
    }

    /**
     * Removes selected or complete event receiver.
     *
     * @param string $id Receiver ID.
     * @param string ...$events Event class name IDs.
     * @throws Error Internal error.
     */
    public static function removeReceiver(string $id, string ...$events): void
    {
        Container::get(Proxy::class)
            ->removeReceiver($id, ...$events);
    }
}