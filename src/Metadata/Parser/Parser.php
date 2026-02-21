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

namespace Valvoid\Fusion\Metadata\Parser;

use Valvoid\Box\Box;

/**
 * Metadata parser.
 */
class Parser
{
    /**
     * Constructs the parser.
     *
     * @param Box $box Dependency injection container.
     */
    public function __construct(private readonly Box $box) {}

    /**
     * Parses meta.
     *
     * @param array $meta Meta.
     */
    public function parse(array &$meta): void
    {
        foreach ($meta as $key => &$value)
            match($key) {
                "structure" => $this->box->get(Structure::class)
                    ->parse($value),

                "environment" => $this->box->get(Environment::class)
                    ->parse($value),

                default => null
            };
    }
}