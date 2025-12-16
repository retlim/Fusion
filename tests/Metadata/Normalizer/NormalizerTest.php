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

namespace Valvoid\Fusion\Tests\Metadata\Normalizer;

use Valvoid\Fusion\Bus\Events\Metadata as MetadataEvent;
use Valvoid\Fusion\Metadata\Normalizer\Normalizer;
use Valvoid\Fusion\Tests\Metadata\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Metadata\Mocks\BusMock;
use Valvoid\Fusion\Tests\Metadata\Mocks\StructureMock;
use Valvoid\Fusion\Tests\Test;

class NormalizerTest extends Test
{
    protected string|array $coverage = Normalizer::class;

    private BoxMock $box;
    private BusMock $bus;
    private StructureMock $structure;

    public function __construct()
    {
        $this->box = new BoxMock;
        $this->bus = new BusMock;
        $this->structure = new StructureMock;
        $this->box->get = function (string $class, ...$args) {
            if ($class == "Valvoid\Fusion\Bus\Events\Metadata")
                return new MetadataEvent(...$args);

            if ($class == "Valvoid\Fusion\Metadata\Normalizer\Structure") {
                $this->structure->args = $args;
                return $this->structure;
            }
        };

        $this->structure->normalize = function (array &$meta, ?string $cache = null) {
            $meta = [
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
                    "extendables" => [],
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
            ];
        };

        $this->testNormalize();
        $this->testOverlay();

        $this->box->unsetInstance();
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

        (new Normalizer($this->box, $this->bus))
            ->normalize($metadata);

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
                    "extendables" => [],
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

        (new Normalizer($this->box, $this->bus))
            ->overlay($content, ["key1" => null, "key2" => "value2"]);

        if ($content !== [
                "key1" => null,
                "whatever",
                "key2" => "value2"
            ]) $this->handleFailedTest();
    }
}