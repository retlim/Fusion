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

namespace Valvoid\Fusion\Hub\APIs\Local\Dir\Config;

use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Config as ConfigEvent;
use Valvoid\Fusion\Config\Interpreter as ConfigInterpreter;
use Valvoid\Fusion\Hub\APIs\Local\Dir\Dir;
use Valvoid\Fusion\Log\Events\Level;

/**
 * Directory config interpreter.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Interpreter extends ConfigInterpreter
{
    /**
     * Interprets the directory config.
     *
     * @param array $breadcrumb Index path inside the config to the directory config.
     * @param mixed $entry Directory config to interpret.
     */
    public static function interpret(array $breadcrumb, mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if (is_string($entry))
            self::interpretDefaultApi($breadcrumb, $entry);

        elseif (is_array($entry))
            foreach ($entry as $key => $value)
                match ($key) {
                    "api" => self::interpretApi($breadcrumb, $value),
                    default => Bus::broadcast(new ConfigEvent(
                        "The unknown \"$key\" index must be " .
                        "\"api\" string.",
                        Level::ERROR,
                        [...$breadcrumb, $key]
                    ))
                };

        else Bus::broadcast(new ConfigEvent(
            "The value must be the default \"" . Dir::class .
            "\" class name string or a configured array API.",
            Level::ERROR,
            $breadcrumb
        ));
    }

    /**
     * Interprets the default api.
     *
     * @param mixed $entry API entry.
     */
    private static function interpretDefaultApi(array $breadcrumb, mixed $entry): void
    {
        if ($entry !== Dir::class)
            Bus::broadcast(new ConfigEvent(
                "The value must be the \"" . Dir::class .
                "\" class name string.",
                Level::ERROR,
                $breadcrumb
            ));
    }

    /**
     * Interprets the API entry.
     *
     * @param mixed $entry API entry.
     */
    private static function interpretApi(array $breadcrumb, mixed $entry): void
    {
        // overlay reset value
        if ($entry === null)
            return;

        if ($entry !== Dir::class)
            Bus::broadcast(new ConfigEvent(
                "The value, API class name, of the \"api\" " .
                "index must be the \"" . Dir::class . "\" string.",
                Level::ERROR,
                [...$breadcrumb, "api"]
            ));
    }
}