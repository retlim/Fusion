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

namespace Valvoid\Fusion\Config\Interpreter;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Events\Config as ConfigEvent;
use Valvoid\Fusion\Bus\Proxy as BusProxy;
use Valvoid\Fusion\Log\Events\Level;

/**
 * Config interpreter.
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class Interpreter
{
    /**
     * Constructs the interpreter.
     *
     * @param Box $box Dependency injection container.
     * @param BusProxy $bus Event bus.
     */
    public function __construct(
        private readonly Box $box,
        private readonly BusProxy $bus) {}

    /**
     * Interprets the config.
     *
     * @param mixed $entry Potential config.
     */
    public function interpret(mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (!is_array($entry) || empty($entry))
            $this->broadcastConfigEvent(
                "The value, config, must be an assoc array.",
                Level::ERROR
            );

        foreach ($entry as $key => $value)
            match($key) {
                "hub" => $this->box->get(Hub::class)
                    ->interpret($value),

                "persistence" => $this->box->get(Persistence::class)
                    ->interpret($value),

                "tasks" => $this->box->get(Tasks::class)
                    ->interpret($value),

                "log" => $this->box->get(Log::class)
                    ->interpret($value),

                // directories are individual and
                // already interpreted
                "dir", "state", "cache", "config" => "",
                default => $this->broadcastConfigEvent(
                    "The unknown '$key' index must be 'dir', " .
                    "'persistence', 'hub', 'tasks' or 'log' string.",
                    Level::ERROR,
                    [$key]
                )};
    }

    /**
     * Broadcasts config event.
     *
     * @param string $message
     * @param Level $level
     * @param array $breadcrumb
     */
    private function broadcastConfigEvent(string $message, Level $level,
                                          array $breadcrumb = []): void
    {
        $this->bus->broadcast(
            $this->box->get(ConfigEvent::class,
                message: $message,
                level: $level,
                breadcrumb: $breadcrumb,
                abstract: []
            ));
    }
}