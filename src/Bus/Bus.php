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

    /** @var array<string, array<string, Closure>> Event receivers. */
    protected array $receivers = [];

    /** Constructs the bus. */
    private function __construct() {}

    /**
     * Returns initial instance or true for recycled instance.
     *
     * @return Bus|bool Instance or recycled.
     */
    public static function ___init(): bool|Bus
    {
        if (self::$instance)
            return true;

        self::$instance = new self;

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
        // decoupled logic
        // trailing underscore identifier
        self::$instance->addReceiver_($id, $callback, ...$events);
    }

    /**
     * Adds event receiver.
     *
     * @param string $id Receiver ID.
     * @param Closure $callback Receiver callback.
     * @param string ...$events Event class name IDs.
     */
    protected function addReceiver_(string $id, Closure $callback, string ...$events): void
    {
        foreach ($events as $event)
            $this->receivers[$event][$id] = $callback;
    }

    /**
     * Removes selected or complete event receiver.
     *
     * @param string $id Receiver ID.
     * @param string ...$events Event class name IDs.
     */
    public static function removeReceiver(string $id, string ...$events): void
    {
        // decoupled logic
        // trailing underscore identifier
        self::$instance->removeReceiver_($id, ...$events);
    }

    /**
     * Removes selected or complete event receiver.
     *
     * @param string $id Receiver ID.
     * @param string ...$events Event class name IDs.
     */
    protected function removeReceiver_(string $id, string ...$events): void
    {
        // clear selected event or
        // complete
        if (!$events)
            $events = array_keys($this->receivers);

        foreach ($events as $event)
            unset($this->receivers[$event][$id]);
    }

    /**
     * Sends the event to all receivers.
     *
     * @param Event $event Event.
     */
    public static function broadcast(Event $event): void
    {
        // decoupled logic
        // trailing underscore identifier
        self::$instance->broadcast_($event);
    }

    /**
     * Sends the event to all receivers.
     *
     * @param Event $event Event.
     */
    protected function broadcast_(Event $event): void
    {
        $receivers = $this->receivers[$event::class] ??

            // fallback
            // broadcast has no confirmation
            [];

        foreach ($receivers as $callback)
            $callback($event);
    }
}