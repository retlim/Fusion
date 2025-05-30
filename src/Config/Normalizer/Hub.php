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

namespace Valvoid\Fusion\Config\Normalizer;

use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Config as ConfigEvent;
use Valvoid\Fusion\Config\Config;
use Valvoid\Fusion\Config\Normalizer;
use Valvoid\Fusion\Log\Events\Level;

/**
 * Hub config normalizer.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Hub
{
    /**
     * Normalizes the hub config.
     *
     * @param array $config Config.
     */
    public static function normalize(array &$config): void
    {
        if (!isset($config["hub"]))
            Bus::broadcast(new ConfigEvent(
                "Missing \"hub\" key.",
                Level::ERROR
            ));

        foreach ($config["hub"]["apis"] as $id => &$entry) {
            if (is_string($entry))
                $entry = [
                    "api" => $entry
                ];

            // configured api
            if (is_array($entry))
                self::normalizeApiConfig($id, $entry);
        }
    }

    /**
     * Normalizes api config.
     *
     * @param string $id API ID.
     * @param array $config Config.
     */
    private static function normalizeApiConfig(string $id, array &$config): void
    {
        $normalizer = substr($config["api"], 0,

                // namespace length
                strrpos($config["api"], '\\')) . "\Config\Normalizer";

        // optional
        // only registered
        if (Config::hasLazy($normalizer)) {
            if (!is_subclass_of($normalizer, Normalizer::class))
                Bus::broadcast(new ConfigEvent(
                    "The auto-generated \"$normalizer\" " .
                    "derivation of the \"api\" value, api config normalizer, " .
                    "must be a string, name of a class that implements the \"" .
                    Normalizer::class . "\" interface.",
                    Level::ERROR,
                    ["hub", "apis", $id, "hub"]
                ));

            $normalizer::normalize(
                ["hub", "apis", $id],
                $config
            );
        }
    }
}