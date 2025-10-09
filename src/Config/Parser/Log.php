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
 * Log config parser.
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class Log
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
     * Parses the log config.
     *
     * @param array $config Log config to parse.
     */
    public function parse(array &$config): void
    {
        foreach ($config["serializers"] as $key => &$value)
            if (is_string($value))
                $value = [
                    "serializer" => $value
                ];

            // configured api or group
            elseif (is_array($value))

                // identifiable
                if (isset($value["serializer"]))
                    $this->parseSerializer(["log", "serializers", $key], $value);

                // identifier in composite layer
                // custom parser already validated in prev layer
                // just pass settings
                elseif ($serializer = $this->config->get("log", "serializers", $key, "serializer")) {
                    $class = substr($serializer, 0,

                            // namespace length
                            strrpos($serializer, '\\')) . "\Config\Parser";

                    // registered file and
                    // implements interface
                    if ($this->config->hasLazy($class)) {
                        $parser = $this->box->get($class);

                        if (!is_subclass_of($parser, ConfigParser::class))
                            $this->broadcastConfigEvent(
                                "The auto-generated '$class' " .
                                "derivation of the 'serializer' value, serializer config parser, " .
                                "must be a string, name of a class that implements the '" .
                                ConfigParser::class . "' interface.",
                                Level::ERROR,
                                ["log", "serializers", $key]
                            );

                        $parser::parse(
                            ["log", "serializers", $key],
                            $value
                        );
                    }
                }
    }

    /**
     * Parses serializer settings.
     *
     * @param array $breadcrumb Index path inside the config.
     * @param array $config
     */
    private function parseSerializer(array $breadcrumb, array &$config): void
    {
        $class = substr($config["serializer"], 0,

                // namespace length
                strrpos($config["serializer"], '\\')) . "\Config\Parser";

        // registered file and
        // implements interface
        if ($this->config->hasLazy($class)) {
            $parser = $this->box->get($class);

            if (!is_subclass_of($parser, ConfigParser::class))
                $this->broadcastConfigEvent(
                    "The auto-generated '$class' " .
                    "derivation of the 'serializer' value, serializer config parser, " .
                    "must be a string, name of a class that implements the '" .
                    ConfigParser::class . "' interface.",
                    Level::ERROR,
                    [...$breadcrumb, "serializer"]
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