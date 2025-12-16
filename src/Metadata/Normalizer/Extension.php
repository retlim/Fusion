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

namespace Valvoid\Fusion\Metadata\Normalizer;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Proxy as Bus;
use Valvoid\Fusion\Bus\Events\Metadata as MetadataEvent;
use Valvoid\Fusion\Log\Events\Level;

/**
 * External meta extension normalizer.
 * @deprecated - remove in 2.0.0
 */
class Extension
{
    /**
     * Constructs the normalizer.
     *
     * @param Box $box Dependency injection container.
     * @param Bus $bus Event bus.
     */
    public function __construct(
        private readonly Box $box,
        private readonly Bus $bus) {}

    /**
     * Normalizes extension.
     *
     * @param array $extension
     * @param array $result
     */
    public function normalize(array $extension, array &$result): void
    {
        foreach ($extension as $path) {

            // remove or jump over nested
            foreach ($result as $i => $p)
                if (str_starts_with($p, $path)) {
                    unset($result[$i]);
                    $this->bus->broadcast(
                        $this->box->get(MetadataEvent::class,
                            message: "Redundant, nested identifier.",
                            level: Level::NOTICE,
                            breadcrumb: ["structure"],
                            abstract: [$path, "extension"]
                        ));

                } elseif (str_starts_with($path, $p)) {
                    $this->bus->broadcast(
                        $this->box->get(MetadataEvent::class,
                            message: "Redundant, nested identifier.",
                            level: Level::NOTICE,
                            breadcrumb: ["structure"],
                            abstract: [$path, "extension"]
                        ));

                    continue 2;
                }

            $result[] = $path;
        }
    }
}