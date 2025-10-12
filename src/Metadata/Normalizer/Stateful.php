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

namespace Valvoid\Fusion\Metadata\Normalizer;

use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Metadata as MetadataEvent;
use Valvoid\Fusion\Log\Events\Level;

/**
 * External meta stateful normalizer.
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class Stateful
{
    /**
     * Normalizes cache.
     *
     * @param array $category
     * @param string $stateful
     */
    public static function normalize(array $category, string &$stateful): void
    {
        if (!$category)
            Bus::broadcast(new MetadataEvent(
                "Missing stateful directory indicator. Structure must have " .
                "unique stateful directory indicator.",
                Level::ERROR,
                ["structure"]
            ));

        $path = $category[0];

        // nested folder
        if (!$path)
            Bus::broadcast(new MetadataEvent(
                "No stateful directory. " .
                "stateful directory indicator must be at a nested directory.",
                Level::ERROR,
                ["structure"],
                [$path, "stateful"]
            ));

        $stateful = $path;
    }
}