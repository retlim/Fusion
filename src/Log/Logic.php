<?php
/*
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
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Valvoid\Fusion\Log;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Config\Config;
use Valvoid\Fusion\Log\Events\Event;
use Valvoid\Fusion\Log\Events\Infos\Error as ErrorInfo;
use Valvoid\Fusion\Log\Events\Interceptor;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Log\Serializers\Files\File;
use Valvoid\Fusion\Log\Serializers\Streams\Stream;

/**
 * Default event log implementation.
 */
class Logic implements Proxy
{
    /** @var File[]|Stream[] Output formatters. */
    protected array $serializers = [];

    /** @var Interceptor Event interceptor. */
    protected Interceptor $interceptor;

    /** Constructs the log. */
    public function __construct(
        private readonly Box $box
    )
    {
        $config = Config::get("log");

        foreach ($config["serializers"] as $serializer)
            $this->serializers[] = $this->box->get($serializer["serializer"],
                config: $serializer);

        // verbose debug log
        // wrap all to extended serializer info
        set_error_handler(function ($code, $message) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

            // clear self-entry
            unset($backtrace[0]);

            // top down flow
            $backtrace = array_reverse($backtrace);

            $this->verbose(new ErrorInfo($message, $code, $backtrace));
        });
    }

    /** Destructs the log instance. */
    public function __destruct()
    {
        restore_error_handler();
    }

    /**
     * Adds event interceptor.
     *
     * @param Interceptor $interceptor Interceptor.
     */
    public function addInterceptor(Interceptor $interceptor): void
    {
        $this->interceptor = $interceptor;
    }

    /** Removes event interceptor. */
    public function removeInterceptor(): void
    {
        unset($this->interceptor);
    }

    /**
     * Logs event.
     *
     * @param Level $level Level.
     * @param Event|string $event Event.
     */
    protected function log(Level $level, Event|string $event): void
    {
        // extend manually
        if (isset($this->interceptor))
            $this->interceptor->extend($event);

        foreach ($this->serializers as $serializer)
            $serializer->log($level, $event);
    }

    /**
     * Logs error event.
     *
     * @param Event|string $event Event.
     */
    public function error(Event|string $event): void
    {
        $this->log(Level::ERROR, $event);
    }

    /**
     * Logs warning event.
     *
     * @param Event|string $event Event.
     */
    public function warning(Event|string $event): void
    {
        $this->log(Level::WARNING, $event);
    }

    /**
     * Logs notice event.
     *
     * @param Event|string $event Event.
     */
    public function notice(Event|string $event): void
    {
        $this->log(Level::NOTICE, $event);
    }

    /**
     * Logs info event.
     *
     * @param Event|string $event Event.
     */
    public function info(Event|string $event): void
    {
        $this->log(Level::INFO, $event);
    }

    /**
     * Logs verbose event.
     *
     * @param Event|string $event Event.
     */
    public function verbose(Event|string $event): void
    {
        $this->log(Level::VERBOSE, $event);
    }

    /**
     * Logs debug event.
     *
     * @param Event|string $event Event.
     */
    public function debug(Event|string $event): void
    {
        $this->log(Level::DEBUG, $event);
    }
}