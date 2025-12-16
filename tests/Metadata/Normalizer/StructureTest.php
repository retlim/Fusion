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
use Valvoid\Fusion\Metadata\Normalizer\Mutable;
use Valvoid\Fusion\Metadata\Normalizer\Source;
use Valvoid\Fusion\Metadata\Normalizer\Stateful;
use Valvoid\Fusion\Metadata\Normalizer\Structure;
use Valvoid\Fusion\Tests\Metadata\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Metadata\Mocks\BusMock;
use Valvoid\Fusion\Tests\Test;

class StructureTest extends Test
{
    protected string|array $coverage = Structure::class;
    private BoxMock $box;
    private BusMock $bus;

    public function __construct()
    {
        $this->box = new BoxMock;
        $this->bus = new BusMock;
        $this->box->get = function (string $class, ...$args) {
            if ($class == "Valvoid\Fusion\Bus\Events\Metadata")
                return new MetadataEvent(...$args);

            if ($class == "Valvoid\Fusion\Metadata\Normalizer\Stateful")
                return new Stateful($this->box, $this->bus);

            if ($class == "Valvoid\Fusion\Metadata\Normalizer\Source")
                return new Source($this->box, $this->bus);

            if ($class == "Valvoid\Fusion\Metadata\Normalizer\Mutable")
                return new Mutable($this->box, $this->bus);
        };

        $this->testNormalize();
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
                "recursive",
                "/state" => [
                    "stateful",
                ],
                "/p5" => [
                    "/p6" => [
                        ":package/id/sub/dir",
                    ]
                ],
                "/path1" => [
                    "source1",
                    "source2",
                ],
                "/p7" => "mutable",
                "/p8" => "extendable",
            ],
            "environment" => []
        ];

        (new Structure($this->box, $this->bus, ""))
            ->normalize($metadata, "layer");

        if ($metadata !== [
                "id" => "",
                "version" => "",
                "name" => "",
                "dir" => "",
                "description" => "",
                "structure" => [
                    "stateful" => "/state",
                    "sources" => [
                        "" => ["recursive"],
                        "/path1" => [
                            "source1",
                            "source2",
                        ]
                    ],
                    "extendables" => [
                        "/p8"
                    ],
                    "mappings" => [
                        "/p5/p6" => ":package/id/sub/dir",
                    ],
                    "mutables" => [
                        "/p7"
                    ]
                ],
                "environment" => []
            ]) $this->handleFailedTest();
    }
}