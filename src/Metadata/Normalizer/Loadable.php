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
 * External meta loadable normalizer.
 * @deprecated - remove in 2.0.0
 */
class Loadable
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
     * Normalizes loadable.
     *
     * @param array $loadable
     * @param string $cache
     * @param array $result
     */
    public function normalize(array $loadable, string $cache, array &$result): void
    {
        if (!$loadable)
            return;

        $dir = "$cache/loadable";
        $cacheLen = strlen($dir);

        foreach ($loadable as $entry)
            foreach ($entry as $namespace => $path) {

                // redundant
                if (array_key_exists($namespace, $result))
                    $this->bus->broadcast(
                        $this->box->get(MetadataEvent::class,
                        message:"Redundant loadable identifier. Namespace already taken.",
                        level:Level::NOTICE,
                        breadcrumb:["structure"],
                        abstract:[$path, $namespace]
                    ));

                else

                    // nested cache folder
                    if (str_starts_with($path, $dir)) {
                        $path = substr($path, $cacheLen);

                        // default, redundant
                        // empty = cache
                        ($path) ?
                            $result[$namespace] = $path :
                            $this->bus->broadcast(
                                $this->box->get(MetadataEvent::class,
                                message:"Redundant loadable identifier. " .
                                "Cache folder is default.",
                                level:Level::NOTICE,
                                breadcrumb:["structure"],
                                abstract:[$path, $namespace]
                            ));

                    } else
                        $this->bus->broadcast(
                            $this->box->get(MetadataEvent::class,
                            message:"External loadable path. Loadable identifier must " .
                            "be inside \"$dir\" cache folder.",
                            level:Level::ERROR,
                            breadcrumb:["structure"],
                            abstract:[$path, $namespace]
                        ));
            }
    }
}