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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Valvoid\Fusion\Log;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Event;
use Valvoid\Fusion\Log\Events\Interceptor;
use Valvoid\Fusion\Log\Proxy\Proxy;

/**
 * Static event log proxy.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Log
{
    /**
     * Adds event interceptor.
     *
     * @param Interceptor $interceptor Interceptor.
     * @throws Error Internal error.
     */
    public static function addInterceptor(Interceptor $interceptor): void
    {
        Box::getInstance()->get(Proxy::class)
            ->addInterceptor($interceptor);
    }

    /**
     * Removes event interceptor.
     *
     * @throws Error Internal error.
     */
    public static function removeInterceptor(): void
    {
        Box::getInstance()->get(Proxy::class)
            ->removeInterceptor();
    }

    /**
     * Logs error event.
     *
     * @param Event|string $event Event.
     * @throws Error Internal error.
     */
    public static function error(Event|string $event): void
    {
        Box::getInstance()->get(Proxy::class)
            ->error($event);
    }

    /**
     * Logs warning event.
     *
     * @param Event|string $event Event.
     * @throws Error Internal error.
     */
    public static function warning(Event|string $event): void
    {
        Box::getInstance()->get(Proxy::class)
            ->warning($event);
    }

    /**
     * Logs notice event.
     *
     * @param Event|string $event Event.
     * @throws Error Internal error.
     */
    public static function notice(Event|string $event): void
    {
        Box::getInstance()->get(Proxy::class)
            ->notice($event);
    }

    /**
     * Logs info event.
     *
     * @param Event|string $event Event.
     * @throws Error Internal error.
     */
    public static function info(Event|string $event): void
    {
        Box::getInstance()->get(Proxy::class)
            ->info($event);
    }

    /**
     * Logs verbose event.
     *
     * @param Event|string $event Event.
     * @throws Error Internal error.
     */
    public static function verbose(Event|string $event): void
    {
        Box::getInstance()->get(Proxy::class)
            ->verbose($event);
    }

    /**
     * Logs debug event.
     *
     * @param Event|string $event Event.
     * @throws Error Internal error.
     */
    public static function debug(Event|string $event): void
    {
        Box::getInstance()->get(Proxy::class)
            ->debug($event);
    }
}