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

namespace Valvoid\Fusion\Config\Parser;

use Valvoid\Fusion\Box\Box;

/**
 * Config parser.
 */
class Parser
{
    /**
     * Constructs the normalizer.
     *
     * @param Box $box Dependency injection container.
     */
    public function __construct(private readonly Box $box) {}

    /**
     * Parses the config.
     *
     * @param array $config Config to parse.
     */
    public function parse(array &$config): void
    {
        foreach ($config as $key => &$value)
            match($key) {
                "tasks" => $this->box->get(Tasks::class)
                    ->parse($value),

                "log" => $this->box->get(Log::class)
                    ->parse($value),

                "hub" => $this->box->get(Hub::class)
                    ->parse($value),

                default => null
            };
    }
}