<?php
/**
 * Fusion. A package manager for PHP-based projects.
 * Copyright Valvoid
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

use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Config as ConfigEvent;
use Valvoid\Fusion\Config\Config;
use Valvoid\Fusion\Config\Parser as ConfigParser;
use Valvoid\Fusion\Log\Events\Level;

/**
 * Hub config parser.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Hub
{
    /**
     * Parses the hub config.
     *
     * @param array $config Hub config to parse.
     */
    public static function parse(array &$config): void
    {
        foreach ($config["apis"] as $key => &$value)
            if (is_string($value))
                $value = [
                    "api" => $value
                ];

            // configured api or group
            elseif (is_array($value))

                // identifiable
                if (isset($value["api"]))
                    self::parseApi(["hub", "apis", $key], $value);

                // identifier in composite layer
                // custom parser already validated in prev layer
                // just pass settings
                elseif ($apiClassName = Config::get("hub", "apis", $key, "api")) {
                    $parser = substr($apiClassName, 0,

                            // namespace length
                            strrpos($apiClassName, '\\')) . "\Config\Parser";

                    // registered file and
                    // implements interface
                    if (Config::hasLazy($parser)) {
                        if (!is_subclass_of($parser, ConfigParser::class))
                            Bus::broadcast(new ConfigEvent(

                                // show auto-generated parser class
                                "The auto-generated \"$parser\" " .
                                "derivation of the \"api\" value, api config parser, " .
                                "must be a string, name of a class that implements the \"" .
                                ConfigParser::class . "\" interface.",
                                Level::ERROR,
                                ["hub", "apis", $key]
                            ));

                        $parser::parse(
                            ["hub", "apis", $key],
                            $value
                        );
                    }
                }
    }

    /**
     * Parses api settings.
     *
     * @param array $breadcrumb Index path inside the config.
     * @param array $config
     */
    private static function parseApi(array $breadcrumb, array &$config): void
    {
        $parser = substr($config["api"], 0,

                // namespace length
                strrpos($config["api"], '\\')) . "\Config\Parser";

        // registered file and
        // implements interface
        if (Config::hasLazy($parser)) {
            if (!is_subclass_of($parser, ConfigParser::class))
                Bus::broadcast(new ConfigEvent(

                    // show auto-generated parser class
                    "The auto-generated \"$parser\" " .
                    "derivation of the \"api\" value, api config parser, " .
                    "must be a string, name of a class that implements the \"" .
                    ConfigParser::class . "\" interface.",
                    Level::ERROR,
                    [...$breadcrumb, "api"]
                ));

            $parser::parse($breadcrumb, $config);
        }
    }
}