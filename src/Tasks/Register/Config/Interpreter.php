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

namespace Valvoid\Fusion\Tasks\Register\Config;

use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Config as ConfigEvent;
use Valvoid\Fusion\Config\Interpreter as ConfigInterpreter;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Tasks\Register\Register;

/**
 * Register task config interpreter.
 */
class Interpreter implements ConfigInterpreter
{
    /**
     * Constructs the interpreter.
     *
     * @param Box $box Dependency injection container.
     * @param Bus $bus Event bus.
     */
    public function __construct(
        private readonly Box $box,
        private readonly Bus $bus) {}

    /**
     * Interprets the register task config.
     *
     * @param array $breadcrumb Index path inside the config to the passed sub config.
     * @param mixed $entry Sub config to interpret.
     */
    public function interpret(array $breadcrumb, mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (is_string($entry))
            $this->interpretDefaultTask($breadcrumb, $entry);

        elseif (is_array($entry))
            foreach ($entry as $key => $value)
                match ($key) {
                    "task" =>  $this->interpretTask($breadcrumb, $value),
                    default => $this->bus->broadcast(
                        $this->box->get(ConfigEvent::class,
                            message: "The unknown \"$key\" index must be " .
                            "\"task\" string.",
                            level: Level::ERROR,
                            breadcrumb: [...$breadcrumb, $key]
                        ))
                };

        else $this->bus->broadcast(
            $this->box->get(ConfigEvent::class,
                message: "The value must be the default \"" . Register::class .
                "\" class name string or a configured array task.",
                level: Level::ERROR,
                breadcrumb: $breadcrumb
            ));
    }

    /**
     * Interprets the default task.
     *
     * @param mixed $entry Task entry.
     */
    private function interpretDefaultTask(array $breadcrumb, mixed $entry): void
    {
        if ($entry !== Register::class)
            $this->bus->broadcast(
                $this->box->get(ConfigEvent::class,
                    message: "The value must be the \"" . Register::class .
                    "\" class name string.",
                    level: Level::ERROR,
                    breadcrumb: $breadcrumb
                ));
    }

    /**
     * Interprets the task.
     *
     * @param mixed $entry Task entry.
     */
    private function interpretTask(array $breadcrumb, mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if ($entry !== Register::class)
            $this->bus->broadcast(
                $this->box->get(ConfigEvent::class,
                    message: "The value, task class name, of the \"task\" " .
                    "index must be the \"" . Register::class . "\" string.",
                    level: Level::ERROR,
                    breadcrumb: [...$breadcrumb, "task"]
                ));
    }
}