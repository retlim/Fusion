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

use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Metadata as MetadataEvent;
use Valvoid\Fusion\Log\Events\Level;

/**
 * Metadata normalizer.
 */
class Normalizer
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
     * Normalizes meta.
     *
     * @param array $meta Meta.
     */
    public function normalize(array &$meta): void
    {
        $meta = $this->removeResetEntries($meta);
        $keys = ["id", "version", "name", "description", "structure",
            "environment"];

        // require
        foreach ($keys as $key)
            if (!isset($meta[$key]))
                $this->bus->broadcast(
                    $this->box->get(MetadataEvent::class,
                        message: "Meta must have '$key' key.",
                        level: Level::ERROR,
                        breadcrumb: [$key]
                    ));

        // empty root package dir or
        // nested package inside parent structure dir and
        // own ID as dir extension
        if ($meta["dir"])
            $meta["dir"] .= "/" . $meta["id"];

        $meta["environment"]["php"]["modules"] ??= [];

        $this->box->get(Structure::class,
            layer: "all")
                ->normalize($meta);

        if (!$meta["structure"]["stateful"])
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "Structure must have a nested state directory.",
                    level: Level::ERROR,
                    breadcrumb: ["structure"]
                ));
    }

    /**
     * Removes reset meta entries.
     *
     * @param array $meta Meta.
     * @return array Cleared meta.
     */
    private function removeResetEntries(array $meta): array
    {
        foreach ($meta as $key => $value) {
            if (is_array($value))
                $meta[$key] = $this->removeResetEntries($value);

            if ($meta[$key] === null)
                unset($meta[$key]);
        }

        return $meta;
    }

    /**
     * Overlays lower meta with higher one.
     *
     * @param array $content Lower meta.
     * @param array $layer Higher meta.
     */
    public function overlay(array &$content, array $layer): void
    {
        foreach ($layer as $key => $value)
            if ($value === null)
                $content[$key] = $value;

            elseif (is_array($value)) {

                // init shell for one to many add rule
                // convert value to array
                if (!isset($content[$key]) || !is_array($content[$key]))
                    $content[$key] = [];

                $this->overlay($content[$key], $value);

                // extend with seq value if not exist
                // one to many add rule
            } elseif (isset($content[$key]) && is_array($content[$key])) {
                if (!in_array($value, $content[$key]))
                    $content[$key][] = $value;

            // override or set
            // one to one add rule
            } else
                $content[$key] = $value;
    }
}