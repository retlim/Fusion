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

namespace Valvoid\Fusion\Log\Proxy;

use Valvoid\Fusion\Log\Events\Event;
use Valvoid\Fusion\Tasks\Task;

/**
 * Default event log proxy instance.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Instance implements Proxy
{
    /** @var Proxy Implementation. */
    protected Proxy $logic;

    /**
     * Constructs the log.
     *
     *  @param Proxy|Logic $logic Any or default logic implementation.
     */
    public function __construct(Proxy|Logic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * Adds task as event interceptor.
     *
     * @param Task $task Task.
     */
    public function addInterceptor(Task $task): void
    {
        $this->logic->addInterceptor($task);
    }

    /** Removes event interceptor. */
    public function removeInterceptor(): void
    {
        $this->logic->removeInterceptor();
    }

    /**
     * Logs error event.
     *
     * @param Event|string $event Event.
     */
    public function error(string|Event $event): void
    {
        $this->logic->error($event);
    }

    /**
     * Logs warning event.
     *
     * @param Event|string $event Event.
     */
    public function warning(string|Event $event): void
    {
        $this->logic->warning($event);
    }

    /**
     * Logs notice event.
     *
     * @param Event|string $event Event.
     */
    public function notice(string|Event $event): void
    {
        $this->logic->notice($event);
    }

    /**
     * Logs info event.
     *
     * @param Event|string $event Event.
     */
    public function info(string|Event $event): void
    {
        $this->logic->info($event);
    }

    /**
     * Logs verbose event.
     *
     * @param Event|string $event Event.
     */
    public function verbose(string|Event $event): void
    {
        $this->logic->verbose($event);
    }

    /**
     * Logs debug event.
     *
     * @param Event|string $event Event.
     */
    public function debug(string|Event $event): void
    {
        $this->logic->debug($event);
    }
}