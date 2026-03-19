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
 * Metadata structure normalizer.
 */
class Structure
{
    /** @var string[] Stateful category. */
    private array $stateful = [];

    /** @var array<string, string[]> Source category. */
    private array $source = [];

    /** @var array<string, string[]> Mapping category. */
    private array $mappings = [];

    /** @var string[] Extension category. */
    private array $extendable = [];

    /** @var string[] Mutable category. */
    private array $mutable = [];

    /**
     * Constructs the normalizer.
     *
     * @param Box $box Dependency injection container.
     * @param Bus $bus Event bus.
     * @param string $layer Current layer identifier.
     */
    public function __construct(
        private readonly Box $box,
        private readonly Bus $bus,
        private readonly string $layer) {}

    /**
     * Normalizes structure.
     *
     * @param array $meta Meta.
     */
    public function normalize(array &$meta): void
    {
        $this->extractStructure($meta["structure"], "", "");

        // replace
        $meta["structure"] = [
            "stateful" => "",
            "sources" => [],
            "extendables" => [],
            "mappings" => [],
            "mutables" => []
        ];

        if ($this->stateful)
            $this->box->get(Stateful::class)
                ->normalize(
                    $this->stateful,
                    $meta["structure"]["stateful"]
                );

        if ($this->source)
            $this->box->get(Source::class)
                ->normalize(
                    $this->source,
                    $meta["structure"]["sources"]
                );

        if ($this->mappings)
            $meta["structure"]["mappings"] = $this->mappings;

        if ($this->extendable)
            $meta["structure"]["extendables"] = $this->extendable;

        if ($this->mutable)
            $this->box->get(Mutable::class)
                ->normalize(
                    $this->mutable,
                    $meta["structure"]["mutables"]
                );
    }

    /**
     * Extracts structure into categories.
     *
     * @param array $structure Structure.
     * @param string $path Directory breadcrumb.
     * @param string $source Source breadcrumb.
     */
    private function extractStructure(array $structure, string $path, string $source): void
    {
        foreach ($structure as $key => $value)
            if (is_array($value))
                if (is_string($key))

                    // has directory identifier
                    // pass dir or source breadcrumb
                    ($key[0] === '/') ?
                        $this->extractStructure($value, $path . $key, $source) :
                        $this->extractStructure($value, $path, "$source/$key");

                // numeric seq
                // pass just value
                else
                    $this->extractStructure($value, $path, $source);

            // stateful dir
            elseif (($value == "stateful" || $value == "cache") && !$source) {
                $entry = ($key[0] ?? null) === '/' ?
                    $path . $key :
                    $path;

                if ($this->layer == "development" || $this->layer == "local")
                    $this->bus->broadcast(
                        $this->box->get(MetadataEvent::class,
                            message: "The 'stateful' indicator is static and belongs to " .
                            "the 'fusion.json' file.",
                            level: Level::ERROR,
                            breadcrumb: ["structure"],
                            abstract: [$entry]
                        ));

                $this->stateful[] = $entry;

            // mutable dir
            } elseif (($value == "mutable" || $value == "state") && !$source)
                $this->mutable[] = ($key[0] ?? null) === '/' ?
                    $path . $key :
                    $path;

            // extendable dir
            elseif (($value == "extendable" || $value == "extension") && !$source)
                $this->extendable[] = ($key[0] ?? null) === '/' ?
                    $path . $key :
                    $path;

            // mapped extension
            elseif (str_starts_with($value, ':') && !$source)
                (($key[0] ?? null) === '/') ?
                    $this->mappings[$path . $key] = $value :
                    $this->mappings[$path] = $value;

            // assoc source reference
            // tag, branch, commit, etc.
            elseif (is_string($key))

                // has directory identifier
                // pass dir and/or source breadcrumb
                $this->source[] = ($key[0] === '/') ?
                    [$path . $key => "$source/$value"] :
                    [$path => "$source/$key/$value"];

            // seq source reference
            // tag, branch, commit, etc.
            else {
                $this->source[] = [
                    $path => "$source/$value"
                ];
            }
    }
}