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

use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Metadata as MetadataEvent;
use Valvoid\Fusion\Log\Events\Level;

/**
 * External meta interpreter.
 */
class Interpreter
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
     * Interprets meta.
     *
     * @param mixed $entry Entry.
     */
    public function interpret(string $layer, mixed $entry): void
    {
        if (!is_array($entry) || empty($entry))
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                message: "Meta must be an assoc array.",
                level: Level::ERROR
            ));

        foreach ($entry as $key => $value)
            match($key) {
                "name" => $this->interpretName($value),
                "description" => $this->interpretDescription($value),
                "lifecycle" => $this->box->get(Lifecycle::class)
                    ->interpret($value),

                "version" => $this->interpretVersion($value),
                "structure" => $this->box->get(Structure::class)
                    ->interpret($value),

                "environment" => $this->box->get(Environment::class)
                    ->interpret($value),

                "id" => $this->interpretId($layer, $value),
                default => $this->bus->broadcast(
                    $this->box->get(MetadataEvent::class,
                        message: "The metadata contains an unknown key '$key'. " .
                        "If it is a valid optional key, updating the package " .
                        "manager may add support for it.",
                        level: Level::NOTICE,
                        breadcrumb: [$key]
                    ))
            };
    }

    /**
     * Interprets name.
     *
     * @param mixed $entry Name.
     */
    private function interpretName(mixed $entry): void
    {
        if ($entry === null)
            return;

        if (!is_string($entry) || !$entry)
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value of the 'name' index must " .
                    "be a non-empty string.",
                    level: Level::ERROR,
                    breadcrumb: ["name"]
                ));
    }

    /**
     * Interprets description.
     *
     * @param mixed $entry
     */
    private function interpretDescription(mixed $entry): void
    {
        if ($entry === null)
            return;

        if (!is_string($entry) || !$entry)
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value of the 'description' index must " .
                    "be a non-empty string.",
                    level: Level::ERROR,
                    breadcrumb: ["description"]
                ));
    }

    /**
     * Interprets ID.
     *
     * @param string $layer Layer.
     * @param mixed $entry ID.
     */
    private function interpretId(string $layer, mixed $entry): void
    {
        if ($layer != "production")
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The 'id' index is static and belongs to " .
                    "the 'fusion.json' file.",
                    level: Level::ERROR,
                    breadcrumb: ["id"]
                ));

        if ($entry === null)
            return;

        if (!preg_match("/^[a-z_][a-z0-9_]{0,20}(\/[a-z_][a-z0-9_]{0,20}){0,4}$/", $entry))
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value of the 'id' index must fit following " .
                    "criteria: Each segment starts with lowercase alphabetic " .
                    "character or underscore. Each segment may consists of lowercase " .
                    "alphabetic characters, underscore or digits. Each segment must " .
                    "be between 1 and 20 characters long. The optional namespace prefix " .
                    "can include up to 4 group names. Segments are separated by a " .
                    "forward slash.",
                    level: Level::ERROR,
                    breadcrumb: ["id"]
                ));
    }

    /**
     * Interprets version.
     *
     * @param mixed $entry Entry.
     */
    private function interpretVersion(mixed $entry): void
    {
        if ($entry === null)
            return;

        if (!preg_match('/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)' .
            '(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-]' .
            '[0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/', $entry))
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value, package version, of the 'version' index " .
                    "must be a semantic version string.",
                    level: Level::ERROR,
                    breadcrumb: ["version"]
                ));
    }
}