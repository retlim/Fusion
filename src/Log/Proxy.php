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

namespace Valvoid\Fusion\Log;

use Valvoid\Fusion\Log\Events\Event;
use Valvoid\Fusion\Log\Events\Interceptor;

/**
 * Event log.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
interface Proxy
{
    /**
     * Adds event interceptor.
     *
     * @param Interceptor $interceptor Interceptor.
     */
    public function addInterceptor(Interceptor $interceptor): void;

    /** Removes event interceptor. */
    public function removeInterceptor(): void;

    /**
     * Logs error event.
     *
     * @param Event|string $event Event.
     */
    public function error(Event|string $event): void;

    /**
     * Logs warning event.
     *
     * @param Event|string $event Event.
     */
    public function warning(Event|string $event): void;

    /**
     * Logs notice event.
     *
     * @param Event|string $event Event.
     */
    public function notice(Event|string $event): void;

    /**
     * Logs info event.
     *
     * @param Event|string $event Event.
     */
    public function info(Event|string $event): void;

    /**
     * Logs verbose event.
     *
     * @param Event|string $event Event.
     */
    public function verbose(Event|string $event): void;

    /**
     * Logs debug event.
     *
     * @param Event|string $event Event.
     */
    public function debug(Event|string $event): void;
}