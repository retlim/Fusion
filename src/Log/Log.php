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

use Valvoid\Fusion\Log\Events\Event;
use Valvoid\Fusion\Log\Events\Interceptor;
use Valvoid\Fusion\Log\Proxy\Logic;
use Valvoid\Fusion\Log\Proxy\Proxy;

/**
 * Static event log proxy.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Log
{
    /** @var ?Log Sharable instance. */
    private static ?Log $instance = null;

    /** @var Proxy Decoupled logic. */
    protected Proxy $proxy;

    /**
     * Constructs the event log.
     *
     * @param Proxy|Logic $proxy Any or default logic.
     */
    private function __construct(Proxy|Logic $proxy)
    {
        // sharable
        self::$instance ??= $this;
        $this->proxy = $proxy;
    }

    /**
     * Adds event interceptor.
     *
     * @param Interceptor $interceptor Interceptor.
     */
    public static function addInterceptor(Interceptor $interceptor): void
    {
        self::$instance->proxy->addInterceptor($interceptor);
    }

    /** Removes event interceptor. */
    public static function removeInterceptor(): void
    {
        self::$instance->proxy->removeInterceptor();
    }

    /**
     * Logs error event.
     *
     * @param Event|string $event Event.
     */
    public static function error(Event|string $event): void
    {
        self::$instance->proxy->error($event);
    }

    /**
     * Logs warning event.
     *
     * @param Event|string $event Event.
     */
    public static function warning(Event|string $event): void
    {
        self::$instance->proxy->warning($event);
    }

    /**
     * Logs notice event.
     *
     * @param Event|string $event Event.
     */
    public static function notice(Event|string $event): void
    {
        self::$instance->proxy->notice($event);
    }

    /**
     * Logs info event.
     *
     * @param Event|string $event Event.
     */
    public static function info(Event|string $event): void
    {
        self::$instance->proxy->info($event);
    }

    /**
     * Logs verbose event.
     *
     * @param Event|string $event Event.
     */
    public static function verbose(Event|string $event): void
    {
        self::$instance->proxy->verbose($event);
    }

    /**
     * Logs debug event.
     *
     * @param Event|string $event Event.
     */
    public static function debug(Event|string $event): void
    {
        self::$instance->proxy->debug($event);
    }
}