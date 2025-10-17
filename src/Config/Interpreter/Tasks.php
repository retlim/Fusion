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

namespace Valvoid\Fusion\Config\Interpreter;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Events\Config as ConfigEvent;
use Valvoid\Fusion\Bus\Proxy as BusProxy;
use Valvoid\Fusion\Config\Interpreter;
use Valvoid\Fusion\Config\Proxy as ConfigProxy;
use Valvoid\Fusion\Log\Events\Level;

/**
 * Tasks config interpreter.
 */
class Tasks
{
    /**
     * Constructs the interpreter.
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
     * Interprets the task config.
     *
     * @param mixed $entry Tasks entry.
     */
    public function interpret(mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (!is_array($entry) || empty($entry))
            $this->broadcastConfigEvent(
                "The value, group, of the 'tasks' " .
                "index must be an assoc array.",
                Level::ERROR,
                ["tasks"]
            );

        foreach ($entry as $key => $value) {
            if (!is_string($key) || !preg_match("/^[a-z]+$/", $key))
                $this->broadcastConfigEvent(
                    "The '$key' index, task/group id, must be an [a-z] string.",
                    Level::ERROR,
                    ["tasks", $key]
                );

            // locked name
            // future functionality
            if ($key == "evaluate" || $key == "cache" || $key == "references" ||
                $key == "metadata" || $key == "package" || $key == "debug" ||
                $key == "push" || $key == "publish" || $key == "release")
                $this->broadcastConfigEvent(
                    "The ID '$key' is locked for a future feature.",
                    Level::ERROR,
                    ["tasks", $key]
                );

            if (is_array($value)) {

                // configured task with type identifier
                if (isset($value["task"]))
                    $this->interpretTaskConfig(["tasks", $key], $value);

                // configured task without type identifier
                // check prev layers
                elseif ($taskClassName = $this->config->get("tasks", $key, "task"))
                    $this->interpretAnonymousTaskConfig($taskClassName, ["tasks", $key], $value);

                // task group
                else $this->interpretGroup($key, $value);

            // default task
            } elseif (is_string($value))
                continue;

            // not reset
            elseif ($value !== null)
                $this->broadcastConfigEvent(
                    "The value, task group, configured or " .
                    "default task, of the '$key' " .
                    "index must be a non-empty string or array.",
                    Level::ERROR,
                    ["tasks", $key]
                );
        }
    }

    /**
     * Interprets task group entry.
     *
     * @param string $group Group id.
     * @param mixed $entry Tasks entry.
     */
    private function interpretGroup(string $group, mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (!is_array($entry) || empty($entry))
            $this->broadcastConfigEvent(
                "The value, task group, of the '$group' " .
                "index must be an assoc array.",
                Level::ERROR,
                ["tasks", $group]
            );

        foreach ($entry as $key => $value) {
            if (!is_string($key) || !preg_match("/^[a-z]+$/", $key))
                $this->broadcastConfigEvent(
                    "The '$key' index, task id, must be an [a-z] string.",
                    Level::ERROR,
                    ["tasks", $group, $key]
                );

            if (is_array($value)) {

                // configured task with type identifier
                if (isset($value["task"]))
                    $this->interpretTaskConfig(["tasks", $group, $key], $value);

                // configured task without type identifier
                // check prev layers
                elseif ($taskClassName = $this->config->get("tasks", $group, $key, "task"))
                    $this->interpretAnonymousTaskConfig($taskClassName, ["tasks", $group, $key], $value);

                // nested group
                else $this->broadcastConfigEvent(
                    "The value, configured task, of the '$key' " .
                    "index must have an identifier, nested 'task' index.",
                    Level::ERROR,
                    ["tasks", $group, $key]
                );

            // default task
            } elseif (is_string($value))
                continue;

            // not reset
            elseif ($value !== null)
                $this->broadcastConfigEvent(
                    "The value, configured or default task, of the '$key' " .
                    "index must be a non-empty array or string.",
                    Level::ERROR,
                    ["tasks", $group, $key]
                );
        }
    }

    /**
     * Interprets configured task.
     *
     * @param array $breadcrumb Index path inside the config.
     * @param array $entry Task config entry to validate.
     */
    private function interpretTaskConfig(array $breadcrumb, array $entry): void
    {
        $task = $entry["task"];

        if (!$this->config->hasLazy($task))
            $this->broadcastConfigEvent(
                "The value, configured task identifier, of the 'task' index must " .
                "be a registered loadable class. Remove this invalid entry from " .
                "the config and execute 'inflate' task to register custom " .
                "lazy code.",
                Level::ERROR,
                [...$breadcrumb, "task"]
            );

        $class = substr($task, 0,

                // namespace length
                strrpos($task, '\\')) . "\Config\Interpreter";

        // registered file and
        // implements interface
        if ($this->config->hasLazy($class)) {
            $interpreter = $this->box->get($class);

            if (!is_subclass_of($interpreter, Interpreter::class))
                $this->broadcastConfigEvent(
                    "The auto-generated '$class' namespace " .
                    "derivation of the 'task' value '$task', task config interpreter, " .
                    "must be a string, name of a class that implements the '" .
                    Interpreter::class . "' interface.",
                    Level::ERROR,
                    $breadcrumb
                );

            $interpreter::interpret($breadcrumb, $entry);
        }
    }

    /**
     * Interprets anonymous (without task identifier) task config.
     *
     * @param string $taskClassName Task class name.
     * @param array $breadcrumb Index path inside the config.
     * @param array $entry Task config entry.
     */
    private function interpretAnonymousTaskConfig(string $taskClassName,
                                                  array $breadcrumb, array $entry): void
    {
        // already validated
        $class = substr($taskClassName, 0,

                // namespace length
                strrpos($taskClassName, '\\')) . "\Config\Interpreter";

        if ($this->config->hasLazy($class)) {
            $interpreter = $this->box->get($class);

            if (!is_subclass_of($interpreter, Interpreter::class))
                $this->broadcastConfigEvent(
                    "The auto-generated '$class' namespace " .
                    "derivation of the 'task' value '$taskClassName', task config interpreter, " .
                    "must be a string, name of a class that implements the '" .
                    Interpreter::class . "' interface.",
                    Level::ERROR,
                    $breadcrumb
                );

            $interpreter::interpret($breadcrumb, $entry);
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