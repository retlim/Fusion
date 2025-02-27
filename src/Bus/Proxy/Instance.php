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
 * Default event bus proxy instance.
 *
 * @Copyright Valvoid
 * @license GNU GPLv3
 */
class Instance implements Proxy
{
    /** @var Proxy Implementation. */
    protected Proxy $logic;

    /** Constructs the bus. */
    public function __construct()
    {
        $this->logic = new Logic;
    }

    /**
     * Adds event receiver.
     *
     * @param string $id Receiver ID.
     * @param Closure $callback Receiver callback.
     * @param string ...$events Event class name IDs.
     */
    public function addReceiver(string $id, Closure $callback, string ...$events): void
    {
        $this->logic->addReceiver($id, $callback, ...$events);
    }

    /**
     * Sends the event to all receivers.
     *
     * @param Event $event Event.
     */
    public function broadcast(Event $event): void
    {
        $this->logic->broadcast($event);
    }

    /**
     * Removes selected or complete event receiver.
     *
     * @param string $id Receiver ID.
     * @param string ...$events Event class name IDs.
     */
    public function removeReceiver(string $id, string ...$events): void
    {
        $this->logic->removeReceiver($id, ...$events);
    }
}