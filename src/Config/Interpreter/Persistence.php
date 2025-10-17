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
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Valvoid\Fusion\Config\Interpreter;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Events\Config as ConfigEvent;
use Valvoid\Fusion\Bus\Proxy as BusProxy;
use Valvoid\Fusion\Log\Events\Level;

/**
 * Persistence config interpreter.
 */
class Persistence
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
     * Interprets the persistence config.
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
                "The value of the 'persistence' index must be an assoc array.",
                Level::ERROR,
                ["persistence"]
            );

        foreach ($entry as $key => $value)
            match($key) {
                "overlay" => $this->interpretOverlay($value),
                default => $this->broadcastConfigEvent(
                    "The unknown '$key' index must be " .
                    "'overlay' string.",
                    Level::ERROR,
                    ["persistence", $key]
                )
            };
    }

    /**
     * Interprets the overlay.
     *
     * @param mixed $entry Entry.
     */
    private function interpretOverlay(mixed $entry): void
    {
        if (!is_bool($entry))
            $this->broadcastConfigEvent(
                "The value, overlay flag, of the index " .
                "'overlay' must be a boolean.",
                Level::ERROR,
                ["persistence", "overlay"]
            );
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