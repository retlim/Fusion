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
use Valvoid\Fusion\Log\Proxy\Instance;
use Valvoid\Fusion\Log\Proxy\Proxy;
use Valvoid\Fusion\Tasks\Task;

/**
 * Event log proxy.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Log
{
    /** @var ?Log Runtime instance. */
    private static ?Log $instance = null;

    /** @var Proxy Decoupled logic. */
    protected Proxy $logic;

    /**
     * Constructs the log.
     *
     * @param Proxy|Instance $logic Any or default instance logic.
     */
    private function __construct(Proxy|Instance $logic)
    {
        // singleton
        self::$instance ??= $this;
        $this->logic = $logic;
    }

    /**
     * Destroys the log instance.
     *
     * @return bool True for success.
     */
    public function destroy(): bool
    {
        self::$instance = null;

        return true;
    }

    /**
     * Adds task as event interceptor.
     *
     * @param Task $task Task.
     */
    public function addInterceptor(Task $task): void
    {
        self::$instance->logic->addInterceptor($task);
    }

    /** Removes event interceptor. */
    public function removeInterceptor(): void
    {
        self::$instance->logic->removeInterceptor();
    }

    /**
     * Logs error event.
     *
     * @param Event|string $event Event.
     */
    public static function error(Event|string $event): void
    {
        self::$instance->logic->error($event);
    }

    /**
     * Logs warning event.
     *
     * @param Event|string $event Event.
     */
    public static function warning(Event|string $event): void
    {
        self::$instance->logic->warning($event);
    }

    /**
     * Logs notice event.
     *
     * @param Event|string $event Event.
     */
    public static function notice(Event|string $event): void
    {
        self::$instance->logic->notice($event);
    }

    /**
     * Logs info event.
     *
     * @param Event|string $event Event.
     */
    public static function info(Event|string $event): void
    {
        self::$instance->logic->info($event);
    }

    /**
     * Logs verbose event.
     *
     * @param Event|string $event Event.
     */
    public static function verbose(Event|string $event): void
    {
        self::$instance->logic->verbose($event);
    }

    /**
     * Logs debug event.
     *
     * @param Event|string $event Event.
     */
    public static function debug(Event|string $event): void
    {
        self::$instance->logic->debug($event);
    }
}