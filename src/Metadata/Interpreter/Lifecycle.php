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
 * Lifecycle interpreter.
 */
class Lifecycle
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
     * Interprets the lifecycle entry.
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
                    message: "The value of the 'lifecycle' index must be an assoc array.",
                    level: Level::ERROR,
                    breadcrumb: ["lifecycle"]
                ));

        foreach ($entry as $key => $value)
            match($key) {
                "download" => $this->interpretDownload($value),
                "copy" => $this->interpretCopy($value),
                "install" => $this->interpretInstall($value),
                "update" => $this->interpretUpdate($value),
                "migrate" => $this->interpretMigrate($value),
                "delete" => $this->interpretDelete($value),
                default => $this->bus->broadcast(
                    $this->box->get(MetadataEvent::class,
                        message: "The unknown '$key' index must be " .
                        "'download', 'copy', 'install', 'update', 'migrate', or " .
                        "'delete' string.",
                        level: Level::ERROR,
                        breadcrumb: ["lifecycle", $key]
                    ))
            };
    }

    /**
     * Interprets the lifecycle download entry.
     *
     * @param mixed $entry Entry.
     */
    private function interpretDownload(mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (!is_string($entry) || !$entry)
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value must be a non-empty string.",
                    level: Level::ERROR,
                    breadcrumb: ["lifecycle", "download"]
                ));

        if ($entry[0] !== '/')
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value must be a file relative to " .
                    "own package root and starting with a leading forward slash.",
                    level: Level::ERROR,
                    breadcrumb: ["lifecycle", "download"]
                ));
    }

    /**
     * Interprets the lifecycle copy entry.
     *
     * @param mixed $entry Entry.
     */
    private function interpretCopy(mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (!is_string($entry) || !$entry)
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value must be a non-empty string.",
                    level: Level::ERROR,
                    breadcrumb: ["lifecycle", "copy"]
                ));

        if ($entry[0] !== '/')
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value must be a file relative to " .
                    "own package root and starting with a leading forward slash.",
                    level: Level::ERROR,
                    breadcrumb: ["lifecycle", "copy"]
                ));
    }

    /**
     * Interprets the lifecycle install entry.
     *
     * @param mixed $entry Entry.
     */
    private function interpretInstall(mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (!is_string($entry) || !$entry)
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value must be a non-empty string.",
                    level: Level::ERROR,
                    breadcrumb: ["lifecycle", "install"]
                ));

        if ($entry[0] !== '/')
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value must be a file relative to " .
                    "own package root and starting with a leading forward slash.",
                    level: Level::ERROR,
                    breadcrumb: ["lifecycle", "install"]
                ));
    }

    /**
     * Interprets the lifecycle delete entry.
     *
     * @param mixed $entry Entry.
     */
    private function interpretDelete(mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (!is_string($entry) || !$entry)
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value must be a non-empty string.",
                    level: Level::ERROR,
                    breadcrumb: ["lifecycle", "delete"]
                ));

        if ($entry[0] !== '/')
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value must be a file relative to " .
                    "own package root and starting with a leading forward slash.",
                    level: Level::ERROR,
                    breadcrumb: ["lifecycle", "delete"]
                ));
    }

    /**
     * Interprets the lifecycle update entry.
     *
     * @param mixed $entry Entry.
     */
    private function interpretUpdate(mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (!is_string($entry) || !$entry)
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value must be a non-empty string.",
                    level: Level::ERROR,
                    breadcrumb: ["lifecycle", "update"]
                ));

        if ($entry[0] !== '/')
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value must be a file relative to " .
                    "own package root and starting with a leading forward slash.",
                    level: Level::ERROR,
                    breadcrumb: ["lifecycle", "update"]
                ));
    }

    /**
     * Interprets the lifecycle migrate entry.
     *
     * @param mixed $entry Entry.
     */
    private function interpretMigrate(mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (!is_string($entry) || !$entry)
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value must be a non-empty string.",
                    level: Level::ERROR,
                    breadcrumb: ["lifecycle", "migrate"]
                ));

        if ($entry[0] !== '/')
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value must be a file relative to " .
                    "own package root and starting with a leading forward slash.",
                    level: Level::ERROR,
                    breadcrumb: ["lifecycle", "migrate"]
                ));
    }
}