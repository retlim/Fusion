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

namespace Valvoid\Fusion\Tasks\Build\Config;

use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Config as ConfigEvent;
use Valvoid\Fusion\Config\Interpreter as ConfigInterpreter;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Tasks\Build\Build;
use Valvoid\Fusion\Util\Version\Interpreter as VersionInterpreter;

/**
 * Build task config interpreter.
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
     * Interprets the build task config.
     *
     * @param array $breadcrumb Index path inside the config.
     * @param mixed $entry Config.
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
                    "environment" => $this->interpretEnvironment($breadcrumb, $value),
                    "task" => $this->interpretTask($breadcrumb, $value),
                    "source" => $this->interpretSource($breadcrumb, $value),
                    default => $this->bus->broadcast(
                        $this->box->get(ConfigEvent::class,
                            message: "The unknown \"$key\" index must be \"task\", " .
                            "\"environment\", or \"source\" string.",
                            level: Level::ERROR,
                            breadcrumb: [...$breadcrumb, $key]
                        ))
                };

        else $this->bus->broadcast(
            $this->box->get(ConfigEvent::class,
                message: "The value must be the default \"" . Build::class .
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
        if ($entry !== Build::class)
            $this->bus->broadcast(
                $this->box->get(ConfigEvent::class,
                    message: "The value must be the \"" . Build::class .
                    "\" class name string.",
                    level: Level::ERROR,
                    breadcrumb: $breadcrumb
                ));
    }

    /**
     * Interprets the task.
     *
     * @param array $breadcrumb Index path inside the config.
     * @param mixed $entry Task entry.
     */
    private function interpretTask(array $breadcrumb, mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if ($entry !== Build::class)
            $this->bus->broadcast(
                $this->box->get(ConfigEvent::class,
                    message: "The value, task class name, of the \"task\" " .
                    "index must be the \"" . Build::class . "\" string.",
                    level: Level::ERROR,
                    breadcrumb: [...$breadcrumb, "task"]
                ));
    }

    /**
     * Interprets the source.
     *
     * @param array $breadcrumb Index path inside the config.
     * @param mixed $entry Source entry.
     */
    private function interpretSource(array $breadcrumb, mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (!is_string($entry) || !$entry)
            $this->bus->broadcast(
                $this->box->get(ConfigEvent::class,
                    message: "The value, source, of the \"source\" " .
                    "index must be a non-empty string.",
                    level: Level::ERROR,
                    breadcrumb: [...$breadcrumb, "source"]
                ));
    }

    /**
     * Interprets the environment.
     *
     * @param array $breadcrumb Index path inside the config.
     * @param mixed $entry Environment entry.
     */
    private function interpretEnvironment(array $breadcrumb, mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (!is_array($entry) || empty($entry))
            $this->bus->broadcast(
                $this->box->get(ConfigEvent::class,
                    message: "The value, environment config, of the \"environment\" " .
                    "index must be an assoc array.",
                    level: Level::ERROR,
                    breadcrumb: [...$breadcrumb, "environment"]
                ));

        foreach ($entry as $key => $value)
            match ($key) {
                "php" => self::interpretPhp($breadcrumb, $value),

                // pass error to builder
                // prevent redundant error handling
                default => $this->bus->broadcast(
                    $this->box->get(ConfigEvent::class,
                        message: "The unknown \"$key\" index must be \"php\" string.",
                        level: Level::ERROR,
                        breadcrumb: [...$breadcrumb, $key]
                    ))
            };
    }

    /**
     * Interprets the php version.
     *
     * @param array $breadcrumb Index path inside the config.
     * @param mixed $entry Php version.
     */
    private function interpretPhp(array $breadcrumb, mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (!is_array($entry) || empty($entry))
            $this->bus->broadcast(
                $this->box->get(ConfigEvent::class,
                    message: "The value of the \"php\" index must be an assoc array.",
                    level: Level::ERROR,
                    breadcrumb: [...$breadcrumb, "environment", "php"]
                ));

        foreach ($entry as $key => $value)
            match ($key) {
                "version" => self::interpretPhpVersion($breadcrumb, $value),

                // pass error to builder
                // prevent redundant error handling
                default => $this->bus->broadcast(
                    $this->box->get(ConfigEvent::class,
                        message: "The unknown \"$key\" index must be \"version\" string.",
                        level: Level::ERROR,
                        breadcrumb: [...$breadcrumb, $key]
                    ))
            };
    }

    /**
     * Interprets the php version.
     *
     * @param array $breadcrumb Index path inside the config.
     * @param mixed $entry PHP version.
     */
    private function interpretPhpVersion(array $breadcrumb, mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (!is_string($entry) || !$entry ||
            !VersionInterpreter::isSemanticCoreVersion($entry))
            $this->bus->broadcast(
                $this->box->get(ConfigEvent::class,
                    message: "The value of the \"version\" index must be a " .
                    "core (major.minor.patch) semantic version string.",
                    level: Level::ERROR,
                    breadcrumb: [...$breadcrumb, "environment", "php", "version"]
                ));
    }
}