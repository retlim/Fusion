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
 * Structure interpreter.
 */
class Structure
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
     * Interprets the structure entry.
     *
     * @param mixed $entry Entry.
     */
    public function interpret(mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (!is_array($entry) || empty($entry))
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value, package structure, of the 'structure' " .
                    "index must be an assoc array.",
                    level: Level::ERROR,
                    breadcrumb: ["structure"]
                ));

        $this->interpretStructure($entry, ["structure"]);
    }

    /**
     * Interprets structure.
     *
     * @param array $entry Structure.
     * @param array $breadcrumb Index path inside meta.
     */
    private function interpretStructure(array $entry, array $breadcrumb): void
    {
        foreach ($entry as $key => $value) {
            if (is_string($key))

                // empty
                if (!$key)
                    $this->bus->broadcast(
                        $this->box->get(MetadataEvent::class,
                            message: "The '$key' index, source or path prefix, " .
                            "must be a non-empty string.",
                            level: Level::ERROR,
                            breadcrumb: [...$breadcrumb, $key]
                        ));

                // path identifier
                elseif ($key[0] === '/') {

                    // only separator
                    if ($key === '/')
                        $this->bus->broadcast(
                            $this->box->get(MetadataEvent::class,
                                message: "The '$key' index, path prefix, " .
                                "must consist of non-empty separated parts. " .
                                "Separator '/' must have trailing chars.",
                                level: Level::ERROR,
                                breadcrumb: [...$breadcrumb, $key]
                            ));
                }

            if (is_array($value))
                $this->interpretStructure($value, [...$breadcrumb, $key]);

            // not reset
            elseif ($value !== null) {

                // non-empty string
                if (!is_string($value) || !$value)
                    $this->bus->broadcast(
                        $this->box->get(MetadataEvent::class,
                            message: "The value, source suffix, of the '$key' index " .
                            "must be a non-empty string.",
                            level: Level::ERROR,
                            breadcrumb: [...$breadcrumb, $key]
                        ));

                // source
                if ($value[0] === '/')
                    $this->bus->broadcast(
                        $this->box->get(MetadataEvent::class,
                            message: "The value, source suffix, of the '$key' index " .
                            "must be of type source. Leading separator '/' is a path " .
                            "type identifier.",
                            level: Level::ERROR,
                            breadcrumb: [...$breadcrumb, $key]
                        ));
            }
        }
    }
}