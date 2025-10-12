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

namespace Valvoid\Fusion\Tests\Metadata\Normalizer;

use Valvoid\Fusion\Metadata\Normalizer\Normalizer;
use Valvoid\Fusion\Tests\Test;

/**
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class NormalizerTest extends Test
{
    protected string|array $coverage = Normalizer::class;

    public function __construct()
    {
        $this->testNormalize();
        $this->testOverlay();
    }

    public function testNormalize(): void
    {
        $metadata = [
            "id" => "",
            "version" => "",
            "name" => "",
            "dir" => "",
            "description" => "",
            "structure" => [
                "/state" => "stateful"
            ],
            "environment" => []
        ];

        Normalizer::normalize($metadata);

        if ($metadata !== [
                "id" => "",
                "version" => "",
                "name" => "",
                "dir" => "",
                "description" => "",
                "structure" => [
                    "cache" => "",
                    "stateful" => "/state",
                    "sources" => [],
                    "extensions" => [],
                    "mappings" => [],
                    "namespaces" => [],
                    "states" => [],
                    "mutables" => []
                ],
                "environment" => [
                    "php" => [
                        "modules" => []
                    ]
                ]
            ]) $this->handleFailedTest();
    }

    public function testOverlay(): void
    {
        $content = [
            "key1" => "value1",
            "whatever"
        ];

        Normalizer::overlay($content, ["key1" => null, "key2" => "value2"]);

        if ($content !== [
                "key1" => null,
                "whatever",
                "key2" => "value2"
            ]) $this->handleFailedTest();
    }
}