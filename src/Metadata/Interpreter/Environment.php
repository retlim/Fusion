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

namespace Valvoid\Fusion\Metadata\Interpreter;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Proxy as Bus;
use Valvoid\Fusion\Bus\Events\Metadata as MetadataEvent;
use Valvoid\Fusion\Log\Events\Level;

/**
 * Environment interpreter.
 */
class Environment
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
     * Interprets the environment entry.
     *
     * @param mixed $entry Potential lifecycle entry.
     */
    public function interpret(mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (!is_array($entry) || empty($entry))
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value, package environment, of the 'environment' " .
                    "index must be an assoc array.",
                    level: Level::ERROR,
                    breadcrumb: ["environment"]
                ));

        foreach ($entry as $key => $value)
            match($key) {
                "php" => $this->interpretPhp($value),
                default => $this->bus->broadcast(
                    $this->box->get(MetadataEvent::class,
                        message: "The unknown '$key' index must be 'php' string.",
                        level: Level::ERROR,
                        breadcrumb: ["environment", $key]
                    ))
            };
    }

    /**
     * Interprets the php entry.
     *
     * @param mixed $entry Entry.
     */
    private function interpretPhp(mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (!is_array($entry) || empty($entry))
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value of the 'php' index must be an assoc array.",
                    level: Level::ERROR,
                    breadcrumb: ["environment", "php"]
                ));

        foreach ($entry as $key => $value)
            match($key) {
                "version" => $this->interpretVersion($value),
                "modules" => $this->interpretModules($value),
                default => $this->bus->broadcast(
                    $this->box->get(MetadataEvent::class,
                        message: "The unknown '$key' index must be " .
                        "'version' or 'modules' string.",
                        level: Level::ERROR,
                        breadcrumb: ["environment", $key]
                    ))
            };
    }

    /**
     * Interprets the version entry.
     *
     * @param mixed $entry Entry.
     */
    private function interpretVersion(mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (!is_string($entry))
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value of the 'version' index must be a string.",
                    level: Level::ERROR,
                    breadcrumb: ["environment", "php", "version"]
                ));
    }

    /**
     * Returns indicator for semantic version core.
     *
     * @param string $entry Entry.
     * @return bool Indicator.
     */
    public function isSemanticVersionCorePattern(string $entry): bool
    {
        return preg_match(
            "/^(>?|>=?|<?|<=?|==?|!=?)" .
            "(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/",
            $entry
        );
    }

    /**
     * Interprets the modules entry.
     *
     * @param mixed $entry Entry.
     */
    private function interpretModules(mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (!is_array($entry) || empty($entry))
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value of the 'modules' index must be an assoc array.",
                    level: Level::ERROR,
                    breadcrumb: ["environment", "php", "modules"]
                ));

        foreach ($entry as $module)
            if (!is_string($module) || !$module)
                $this->bus->broadcast(
                    $this->box->get(MetadataEvent::class,
                        message: "The value of the 'modules' index must be a seq " .
                        "string array.",
                        level: Level::ERROR,
                        breadcrumb: ["environment", "php", "modules"]
                    ));
    }
}