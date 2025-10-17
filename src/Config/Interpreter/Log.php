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
use Valvoid\Fusion\Log\Serializers\Files\File;
use Valvoid\Fusion\Log\Serializers\Streams\Stream;

/**
 * Log config interpreter.
 */
class Log
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
     * Interprets the log config.
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
                "The value of the 'log' index must be an assoc array.",
                Level::ERROR,
                ["log"]
            );

        foreach ($entry as $key => $value)
            match($key) {
                "serializers" => $this->interpretSerializers($value),
                default => $this->broadcastConfigEvent(
                    "The unknown '$key' index must be " .
                    "'serializers' string.",
                    Level::ERROR,
                    ["log", $key]
                )
            };
    }

    /**
     * Interprets log serializers.
     *
     * @param mixed $entry Entry.
     */
    private function interpretSerializers(mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (!is_array($entry) || empty($entry))
            $this->broadcastConfigEvent(
                "The value, serializers group, of the 'serializers' " .
                "index must be an assoc array.",
                Level::ERROR,
                ["log", "serializers"]
            );

        foreach ($entry as $key => $value) {
            if (is_int($key) || !$key)
                $this->broadcastConfigEvent(
                    "The '$key' index, serializer id, must be a non-empty string.",
                    Level::ERROR,
                    ["log", "serializers", $key]
                );

            // configured serializer with/out type identifier
            if (is_array($value))
                (isset($value["serializer"])) ?
                    $this->interpretSerializerConfig($key, $value) :
                    $this->interpretAnonymousSerializerConfig($key, $value);

            // default serializer
            // just class name without config
            elseif (is_string($value))
                continue;

            // overlay reset
            elseif ($value === null)
                continue;

            else $this->broadcastConfigEvent(
                "The value, configured or default serializer, of the '$key' " .
                "index must be a non-empty array or string.",
                Level::ERROR,
                ["log", "serializers", $key]
            );
        }
    }

    /**
     * Interprets serializer config.
     *
     * @param string $id Serializer id.
     * @param array $entry Config.
     */
    private function interpretSerializerConfig(string $id, array $entry): void
    {
        $serializer = $entry["serializer"];

        if (!$this->config->hasLazy($serializer))
            $this->throwUnregisteredSerializerError(
                ["log", "serializers", $id, "serializer"],
                $id
            );

        $class = $this->getInterpreter($serializer);

        if ($this->config->hasLazy($class)) {
            $interpreter = $this->box->get($class);

            if (!is_subclass_of($interpreter, Interpreter::class))
                $this->broadcastConfigEvent(
                    "The auto-generated '$class' namespace " .
                    "derivation of the 'serializer' value '$serializer', " .
                    "serializer config interpreter, " .
                    "must be a string, name of a class that implements the '" .
                    Interpreter::class . "' interface.",
                    Level::ERROR,
                    ["log", "serializers", $id, "serializer"]
                );

            $interpreter::interpret(
                ["log", "serializers", $id, "serializer"],
                $entry
            );
        }
    }

    /**
     * Interprets anonymous serializer config. A layer without serializer identifier.
     *
     * @param string $id Serializer id.
     * @param array $entry Config.
     */
    private function interpretAnonymousSerializerConfig(string $id, array $entry): void
    {
        $serializer = $this->config->get("log", "serializers", $id, "serializer");

        if (!$serializer)
            $this->throwSerializerSubclassError(
                ["log", "serializers", $id],
                $id
            );

        // validated by previous identified hub layer
        $class = $this->getInterpreter($serializer);

        if ($this->config->hasLazy($class)) {
            $interpreter = $this->box->get($class);

            $interpreter::interpret(
                ["log", "serializers", $id],
                $entry
            );
        }
    }

    /**
     * Returns interpreter class name.
     *
     * @param string $serializer Serializer class name.
     * @return Interpreter::class Interpreter.
     */
    private function getInterpreter(string $serializer): string
    {
        return substr($serializer, 0,

                // namespace length
                strrpos($serializer, '\\')) . "\Config\Interpreter";
    }

    /**
     * Throws unregistered serializer class error.
     *
     * @param array $breadcrumb Breadcrumb.
     * @param string $id Serializer ID.
     */
    private function throwUnregisteredSerializerError(array $breadcrumb, string $id): void
    {
        $this->broadcastConfigEvent(
            "The value, default serializer identifier, of the '$id' index must " .
            "be a registered loadable class. Remove this invalid entry from " .
            "the config and execute 'inflate' task to register custom " .
            "lazy code.",
            Level::ERROR,
            $breadcrumb
        );
    }

    /**
     * Throws serializer subclass interface error.
     *
     * @param array $breadcrumb Breadcrumb.
     * @param string $id Serializer ID.
     */
    private function throwSerializerSubclassError(array $breadcrumb, string $id): void
    {
        $this->broadcastConfigEvent(
            "The value, configured serializer config, of the '$id' " .
            "index must have the 'serializer' index with a string value, name of " .
            "a class that implements the '" . File::class . "', or '" .
            Stream::class . "' class.",
            Level::ERROR,
            $breadcrumb
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