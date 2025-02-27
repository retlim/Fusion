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
use Valvoid\Fusion\Bus\Proxy\Instance;
use Valvoid\Fusion\Bus\Proxy\Proxy;

/**
 * Event bus proxy.
 *
 * @Copyright Valvoid
 * @license GNU GPLv3
 */
class Bus
{
    /** @var ?Bus Runtime instance. */
    private static ?Bus $instance = null;

    /** @var Proxy Decoupled logic. */
    protected Proxy $logic;

    /**
     * Constructs the bus.
     *
     * @param Proxy $logic Logic.
     */
    private function __construct(Proxy $logic)
    {
        $this->logic = $logic;
    }

    /**
     * Returns initial instance or true for recycled instance.
     *
     * @return Bus|bool Instance or recycled.
     */
    public static function ___init(): bool|Bus
    {
        if (self::$instance)
            return true;

        self::$instance = new self(new Instance);

        return self::$instance;
    }

    /**
     * Destroys the bus instance.
     *
     * @return bool True for success.
     */
    public function destroy(): bool
    {
        self::$instance = null;

        return true;
    }

    /**
     * Adds event receiver.
     *
     * @param string $id Receiver ID.
     * @param Closure $callback Receiver callback.
     * @param string ...$events Event class name IDs.
     */
    public static function addReceiver(string $id, Closure $callback, string ...$events): void
    {
        self::$instance->logic->addReceiver($id, $callback, ...$events);
    }

    /**
     * Sends the event to all receivers.
     *
     * @param Event $event Event.
     */
    public static function broadcast(Event $event): void
    {
        self::$instance->logic->broadcast($event);
    }

    /**
     * Removes selected or complete event receiver.
     *
     * @param string $id Receiver ID.
     * @param string ...$events Event class name IDs.
     */
    public static function removeReceiver(string $id, string ...$events): void
    {
        self::$instance->logic->removeReceiver($id, ...$events);
    }
}