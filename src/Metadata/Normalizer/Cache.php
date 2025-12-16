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
 * External meta cache normalizer.
 * @deprecated - remove in 2.0.0
 */
class Cache
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
     * Normalizes cache.
     *
     * @param array $category
     * @param string $cache
     */
    public function normalize(array $category, string &$cache): void
    {
        // require structure info
        // cache folder
        if (!$category)
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "Missing cache folder identifier. Structure must have " .
                    "unique cache folder identifier.",
                    level: Level::ERROR,
                    breadcrumb: ["structure"]
                ));

        $path = $category[0];

        // nested folder
        if (!$path)
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "No cache directory. " .
                    "Cache folder identifier must be at a nested directory.",
                    level: Level::ERROR,
                    breadcrumb: ["structure"],
                    abstract: [$path, "cache"]
                ));

        $cache = $path;
    }
}