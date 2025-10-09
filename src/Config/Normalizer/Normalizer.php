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

namespace Valvoid\Fusion\Config\Normalizer;

use Valvoid\Fusion\Box\Box;

/**
 * Config normalizer.
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class Normalizer
{
    /**
     * Constructs the normalizer.
     *
     * @param Box $box Dependency injection container.
     */
    public function __construct(private readonly Box $box) {}

    /**
     * Normalizes the config.
     *
     * @param array $config Config.
     */
    public function normalize(array &$config): void
    {
        $config = $this->removeResetEntries($config);

        foreach ($config as $key => &$value)
            match($key) {
                "tasks" => $this->box->get(Tasks::class)
                    ->normalize($value),

                "log" => $this->box->get(Log::class)
                    ->normalize($value),

                "hub" => $this->box->get(Hub::class)
                    ->normalize($value),

                default => null
            };
    }

    /**
     * Removes reset config entries.
     *
     * @param array $config Config.
     * @return array Cleared config.
     */
    private function removeResetEntries(array $config): array
    {
        foreach ($config as $key => $value) {
            if (is_array($value))
                $config[$key] = $this->removeResetEntries($value);

            if ($config[$key] === null)
                unset($config[$key]);
        }

        return $config;
    }

    /**
     * Overlays composite config.
     *
     * @param array $config Composite config.
     * @param array $layer On top config.
     */
    public function overlay(array &$config, array $layer): void
    {
        foreach ($layer as $key => $value)
            if ($value === null)
                $config[$key] = $value;

            elseif (is_array($value)) {

                // init shell for one to many add rule
                if (!isset($config[$key]) || !is_array($config[$key]))
                    $config[$key] = [];

                $this->overlay($config[$key], $value);

            // extend with seq value if not exist
            // one to many add rule
            } elseif (isset($config[$key]) && is_array($config[$key])) {
                if (!in_array($value, $config[$key]))
                    $config[$key][] = $value;

            // override or set
            // one to one add rule
            } else $config[$key] = $value;
    }
}