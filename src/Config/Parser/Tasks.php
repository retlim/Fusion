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

namespace Valvoid\Fusion\Config\Parser;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Events\Config as ConfigEvent;
use Valvoid\Fusion\Bus\Proxy as BusProxy;
use Valvoid\Fusion\Config\Parser as ConfigParser;
use Valvoid\Fusion\Config\Proxy as ConfigProxy;
use Valvoid\Fusion\Log\Events\Level;

/**
 * Tasks config parser.
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class Tasks
{
    /**
     * Constructs the normalizer.
     *
     * @param Box $box Dependency injection container.
     * @param ConfigProxy $config Config.
     * @param BusProxy $bus Event bus.
     */
    public function __construct(
        private readonly Box $box,
        private readonly ConfigProxy $config,
        private readonly BusProxy $bus) {}

    /**
     * Parses the tasks config.
     *
     * @param array $config Tasks config to parse.
     */
    public function parse(array &$config): void
    {
        foreach ($config as $key => &$value)
            if (is_string($value))
                $value = [
                    "task" => $value
                ];

            // configured task or group
            elseif (is_array($value))

                // identifiable
                if (isset($value["task"]))
                    $this->parseTask(["tasks", $key], $value);

                // identifier in composite layer
                // custom parser already validated in prev layer
                // just pass settings
                elseif ($task = $this->config->get("tasks", $key, "task")) {
                    $class = substr($task, 0,

                            // namespace length
                            strrpos($task, '\\')) . "\Config\Parser";

                    // registered file and
                    // implements interface
                    if ($this->config->hasLazy($class)) {
                        $parser = $this->box->get($class);

                        if (!is_subclass_of($parser, ConfigParser::class))
                            $this->broadcastConfigEvent(
                                "The auto-generated '$class' " .
                                "derivation of the 'task' value, task config parser, " .
                                "must be a string, name of a class that implements the '" .
                                ConfigParser::class . "' interface.",
                                Level::ERROR,
                                ["tasks", $key]
                            );

                        $parser::parse(
                            ["tasks", $key],
                            $value
                        );
                    }

                // task group
                } else $this->parseGroup($key, $value);
    }

    /**
     * Parses task group.
     *
     * @param string $groupId Group id.
     * @param array $config Settings.
     */
    private function parseGroup(string $groupId, array &$config): void
    {
        foreach ($config as $taskId => &$task)

            // configured task
            if (is_array($task)) {
                $breadcrumb = ["tasks", $groupId, $taskId];

                // identifiable
                if (isset($task["task"])) {
                    $this->parseTask($breadcrumb, $task);

                // identifier in composite layer
                } else {
                    $task["task"] = $this->config->get(...[...$breadcrumb, "task"]);

                    // custom parser already validated in prev layer
                    // just pass settings
                    $class = substr($task["task"], 0,

                            // namespace length
                            strrpos($task["task"], '\\')) . "\Config\Parser";

                    // registered file and
                    // implements interface
                    if ($this->config->hasLazy($class)) {
                        $parser = $this->box->get($class);

                        if (!is_subclass_of($parser, ConfigParser::class))
                            $this->broadcastConfigEvent(
                                "The auto-generated '$class' " .
                                "derivation of the 'task' value, task config parser, " .
                                "must be a string, name of a class that implements the '" .
                                ConfigParser::class . "' interface.",
                                Level::ERROR,
                                ["tasks", $groupId, $taskId]
                            );

                        $parser::parse($breadcrumb, $task);
                    }
                }

            } elseif(is_string($task))
                $task = [
                    "task" => $task
                ];
    }

    /**
     * Parses task settings.
     *
     * @param array $breadcrumb Index path inside the config.
     * @param array $config
     */
    private function parseTask(array $breadcrumb, array &$config): void
    {
        $class = substr($config["task"], 0,

                // namespace length
                strrpos($config["task"], '\\')) . "\Config\Parser";

        // registered file and
        // implements interface
        if ($this->config->hasLazy($class)) {
            $parser = $this->box->get($class);

            if (!is_subclass_of($parser, ConfigParser::class))
                $this->broadcastConfigEvent(
                    "The auto-generated '$class' " .
                    "derivation of the 'task' value, task config parser, " .
                    "must be a string, name of a class that implements the '" .
                    ConfigParser::class . "' interface.",
                    Level::ERROR,
                    [...$breadcrumb, "task"]
                );

            $parser::parse($breadcrumb, $config);
        }
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