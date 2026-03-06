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

namespace Valvoid\Fusion\Log\Serializers\Files\JSON\Config;

use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Config as ConfigEvent;
use Valvoid\Fusion\Config\Interpreter as ConfigInterpreter;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Log\Serializers\Files\JSON\JSON;

/**
 * JSON file log config interpreter.
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
     * Interprets the JSON serializer config.
     *
     * @param array $breadcrumb Index path inside the config to the JSON config.
     * @param mixed $entry Config to interpret.
     */
    public function interpret(array $breadcrumb, mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (is_string($entry))
            $this->interpretDefaultSerializer($breadcrumb, $entry);

        elseif (is_array($entry))
            foreach ($entry as $key => $value)
                match ($key) {
                    "serializer" => $this->interpretConfiguredSerializer($breadcrumb, $value),
                    "threshold" => $this->interpretThreshold($breadcrumb, $value),
                    "filename" => $this->interpretFilename($breadcrumb, $value),
                    default => $this->bus->broadcast(
                        $this->box->get(ConfigEvent::class,
                        message: "The unknown '$key' index must be " .
                        "'serializer', 'filename', or 'threshold' string.",
                        level: Level::ERROR,
                        breadcrumb: [...$breadcrumb, $key]
                    ))
                };

        else $this->bus->broadcast(
            $this->box->get(ConfigEvent::class,
                message: "The value must be the default '" . JSON::class .
                "' class name string or a configured array serializer.",
                level: Level::ERROR,
                breadcrumb: $breadcrumb
            ));
    }

    /**
     * Interprets the default serializer.
     *
     * @param mixed $entry Serializer entry.
     */
    private function interpretDefaultSerializer(array $breadcrumb, mixed $entry): void
    {
        if ($entry !== JSON::class)
            $this->bus->broadcast(
                $this->box->get(ConfigEvent::class,
                    message: "The value must be the '" . JSON::class .
                    "' class name string.",
                    level: Level::ERROR,
                    breadcrumb: $breadcrumb
                ));
    }

    /**
     * Interprets the serializer entry.
     *
     * @param mixed $entry Serializer entry.
     */
    private function interpretConfiguredSerializer(array $breadcrumb, mixed $entry): void
    {
        // overlay reset value
        if ($entry === null || $entry === JSON::class)
            return;

        $this->bus->broadcast(
            $this->box->get(ConfigEvent::class,
                message: "The value, serializer class name, of the 'serializer' " .
                "index must be the '" . JSON::class . "' string.",
                level: Level::ERROR,
                breadcrumb: [...$breadcrumb, "serializer"]
            ));
    }

    /**
     * Interprets the filename entry.
     *
     * @param mixed $entry Filename entry.
     */
    private function interpretFilename(array $breadcrumb, mixed $entry): void
    {
        // overlay reset value
        if ($entry === null || $entry == "Y.m.d"  || $entry == "Y.m" ||
            $entry == "Y.m.d_H:i" || $entry == "Y.m.d_H" || $entry == "Y")
            return;

        $this->bus->broadcast(
            $this->box->get(ConfigEvent::class,
                message: "The value of the 'filename' " .
                "index must be the 'Y.m.d', 'Y.m', 'Y.m.d_H:i', " .
                "'Y.m.d_H', or 'Y' string.",
                level: Level::ERROR,
                breadcrumb: [...$breadcrumb, "filename"]
            ));
    }

    /**
     *  Interprets the threshold entry.
     *
     * @param mixed $entry Threshold entry.
     */
    private function interpretThreshold(array $breadcrumb, mixed $entry): void
    {
        // overlay reset value
        if ($entry === null || $entry instanceof Level)
            return;

        if (!is_string($entry) || Level::tryFromName($entry) === null)
            $this->bus->broadcast(
                $this->box->get(ConfigEvent::class,
                    message: "The value of the 'threshold' " .
                    "index must be a case or a related value of the '" .
                    Level::class . "'.",
                    level: Level::ERROR,
                    breadcrumb: [...$breadcrumb, "threshold"]
                ));
    }
}