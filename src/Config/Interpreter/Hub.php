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
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Valvoid\Fusion\Config\Interpreter;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Events\Config as ConfigEvent;
use Valvoid\Fusion\Config\Interpreter;
use Valvoid\Fusion\Config\Proxy as ConfigProxy;
use Valvoid\Fusion\Hub\APIs\Local\Local;
use Valvoid\Fusion\Hub\APIs\Remote\Remote;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Bus\Proxy as BusProxy;

/**
 * Hub config interpreter.
 */
class Hub
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
     * Interprets the hub config.
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
                "The value of the 'hub' index must be an assoc array.",
                Level::ERROR,
                ["hub"]
            );

        foreach ($entry as $key => $value)
            match($key) {
                "apis" => $this->interpretApis($value),
                default => $this->broadcastConfigEvent(
                    "The unknown '$key' index must be " .
                    "'apis' string.",
                    Level::ERROR,
                    ["hub", $key]
                )};
    }

    /**
     * Interprets hub apis.
     *
     * @param mixed $entry
     */
    private function interpretApis(mixed $entry): void
    {
        if ($entry === null)
            return;

        if (!is_array($entry) || empty($entry))
            $this->broadcastConfigEvent(
                "The value, apis group, of the 'apis' " .
                "index must be an assoc array.",
                Level::ERROR,
                ["hub", "apis"]
            );

        foreach ($entry as $key => $value) {
            if (is_int($key) || !$key)
                $this->broadcastConfigEvent(
                    "The '$key' index, api id, must be a non-empty string.",
                    Level::ERROR,
                    ["hub", "apis", $key]
                );

            // configured api with/out type identifier
            if (is_array($value))
                (isset($value["api"])) ?
                    $this->interpretApiConfig($key, $value) :
                    $this->interpretAnonymousApiConfig($key, $value);

            // default api
            // just class name without config
            // nothing to interpret
            elseif (is_string($value))
                continue;

            // overlay reset
            elseif ($value === null)
                continue;

            else $this->broadcastConfigEvent(
                "The value, configured or default api, of the '$key' " .
                "index must be a non-empty array or string.",
                Level::ERROR,
                ["hub", "apis", $key]
            );
        }
    }

    /**
     * Interprets API config.
     *
     * @param string $id API id.
     * @param array $entry Config.
     */
    private function interpretApiConfig(string $id, array $entry): void
    {
        $api = $entry["api"];

        if (!$this->config->hasLazy($api))
            $this->throwUnregisteredApiError(
                ["hub", "apis", $id, "api"],
                $id
            );

        $class = $this->getInterpreter($api);

        if ($this->config->hasLazy($class)) {
            $interpreter = $this->box->get($class);

            if (!is_subclass_of($interpreter, Interpreter::class))
                $this->broadcastConfigEvent(
                    "The auto-generated '$class' namespace " .
                    "derivation of the 'api' value '$api', " .
                    "api config interpreter, must be a string, name of " .
                    "a class that implements the '" . Interpreter::class .
                    "' interface.",
                    Level::ERROR,
                    ["hub", "apis", $id, "api"]
                );

            $interpreter::interpret(
                ["hub", "apis", $id, "api"],
                $entry
            );
        }
    }

    /**
     * Interprets anonymous API config. A layer without API identifier.
     *
     * @param string $id API id.
     * @param array $entry Config.
     */
    private function interpretAnonymousApiConfig(string $id, array $entry): void
    {
        $api = $this->config->get("hub", "apis", $id, "api");

        if (!$api)
            $this->throwApiSubclassError(
                ["hub", "apis", $id],
                $id
            );

        // validated by previous identified hub layer
        $class = $this->getInterpreter($api);

        if ($this->config->hasLazy($class)) {
            $interpreter = $this->box->get($class);

            $interpreter::interpret(
                ["hub", "apis", $id],
                $entry
            );
        }
    }

    /**
     * Returns interpreter class name.
     *
     * @param string $api API class name.
     * @return Interpreter::class Interpreter.
     */
    private function getInterpreter(string $api): string
    {
        return substr($api, 0,

                // namespace length
                strrpos($api, '\\')) . "\Config\Interpreter";
    }

    /**
     * Throws unregistered API class error.
     *
     * @param array $breadcrumb Breadcrumb.
     * @param string $id API ID.
     */
    private function throwUnregisteredApiError(array $breadcrumb, string $id): void
    {
        $this->broadcastConfigEvent(
            "The value, default api identifier, of the '$id' index must " .
            "be a registered loadable class. Remove this invalid entry from " .
            "the config and execute 'inflate' task to register custom " .
            "lazy code.",
            Level::ERROR,
            $breadcrumb
        );
    }

    /**
     * Throws API subclass interface error.
     *
     * @param array $breadcrumb Breadcrumb.
     * @param string $id API ID.
     */
    private function throwApiSubclassError(array $breadcrumb, string $id): void
    {
        $this->broadcastConfigEvent(
            "The value, configured API config, of the '$id' " .
            "index must have the 'api' index with a string value, name of " .
            "a class that implements the '" . Local::class . "' or '" .
            Remote::class . "' class.",
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