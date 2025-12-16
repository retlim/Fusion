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

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Proxy as Bus;
use Valvoid\Fusion\Bus\Events\Metadata as MetadataEvent;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Metadata\Interpreter\Environment as EnvironmentInterpreter;

/**
 * Environment parser.
 */
class Environment
{
    /**
     * Constructs the parser.
     *
     * @param Box $box Dependency injection container.
     * @param Bus $bus Event bus.
     */
    public function __construct(
        private readonly Box $box,
        private readonly Bus $bus) {}

    /**
     * Parses environment entry.
     *
     * @param array $environment
     */
    public function parse(array &$environment): void
    {
        foreach ($environment as $key => &$value)
            match($key) {
                "php" => $this->parsePhp($value),
                default => null
            };
    }

    /**
     * Parses php entry.
     *
     * @param array $php
     */
    private function parsePhp(array &$php): void
    {
        foreach ($php as $key => &$value)
            match($key) {
                "version" => $this->parsePhpVersion($value),
                default => null
            };
    }

    /**
     * Parses php version entry.
     *
     * @param string $version
     */
    private function parsePhpVersion(string &$version): void
    {
        $inline = $version;
        $version = [];

        $this->inflateReference($inline, $version);
    }

    /**
     * Inflates condition.
     *
     * @param array $inflated Inflated condition.
     * @param int $i Inline char pointer.
     */
    private function inflateReference(string $inline, array &$inflated, int &$i = 0): void
    {
        $reference = "";

        for (; $i < strlen($inline); ++$i)
            switch ($inline[$i]) {
                case '(':
                    $reference = trim($reference);

                    if ($reference)
                        $inflated[] = $this->getReference($reference);

                    $nested = [];

                    ++$i;
                    $this->inflateReference($inline, $nested,$i);

                    // nested condition
                    $inflated[] = $nested;

                    break;

                case '|':
                case '&':

                    // trailing logical && and || or
                    if ((($inline[$i - 1] ?? null) !== $inline[$i])) {

                        // prevent empty get reference
                        // nested was added
                        if (isset($nested))
                            unset($nested);

                        $reference = trim($reference);

                        if ($reference)
                            $inflated[] = $this->getReference($reference);

                        $inflated[] = $inline[$i] . $inline[$i];
                        $reference = "";
                    }

                    break;

                case ')': break 2;
                default:
                    $reference .= $inline[$i];
            }

        $reference = trim($reference);

        if ($reference)
            $inflated[] = $this->getReference($reference);
    }

    /**
     * Returns absolute or inflated semantic reference.
     *
     * @param string $reference Inline reference.
     * @return array Reference.
     */
    private function getReference(string $reference): array
    {
        if (!$this->box->get(EnvironmentInterpreter::class)
                ->isSemanticVersionCorePattern($reference))
            $this->bus->broadcast(
                $this->box->get(MetadataEvent::class,
                    message: "The value of the 'version' index must be a " .
                    "core (major.minor.patch) semantic version pattern logic.",
                    level: Level::ERROR,
                    breadcrumb: ["environment", "php", "version"]
                ));

        if (!is_numeric($reference[0])) {
            $sign = $reference[0];

            if ($reference[1] == '=')
                $sign .= $reference[1];

            $reference = ltrim($reference,  $sign);
        }

        $version = explode('.', $reference, 3);

        return [
            "major" => $version[0],
            "minor" => $version[1],
            "patch" => $version[2],

            // future?
            "build" => "",
            "release" => "",
            "sign" => $sign ?? ""
        ];
    }
}