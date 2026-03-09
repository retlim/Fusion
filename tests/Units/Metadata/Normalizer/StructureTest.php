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

namespace Valvoid\Fusion\Tests\Units\Metadata\Normalizer;

use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Metadata\Normalizer\Mutable;
use Valvoid\Fusion\Metadata\Normalizer\Source;
use Valvoid\Fusion\Metadata\Normalizer\Stateful;
use Valvoid\Fusion\Metadata\Normalizer\Structure;
use Valvoid\Reflex\Test\Wrapper;

class StructureTest extends Wrapper
{
    public function testNormalize(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $stateful = $this->createMock(Stateful::class);
        $source = $this->createMock(Source::class);
        $mutable = $this->createMock(Mutable::class);
        $structure = new Structure(
            box: $box,
            bus: $bus,
            layer: ""
        );

        $metadata = [
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
            ]
        ];

        $box->fake("get")
            ->expect(class: Stateful::class)
            ->return($stateful)
            ->expect(class: Source::class)
            ->return($source)
            ->expect(class: Mutable::class)
            ->return($mutable);

        $stateful->fake("normalize")
            ->expect(category: ["/state"], stateful: "")
            ->set(stateful: "#0");

        $source->fake("normalize")
            ->expect(category: [
                ["" => "/recursive"],
                ["/path1" => "/source1"],
                ["/path1" => "/source2"]], sources: [])
            ->set(sources: ["#1"]);

        $mutable->fake("normalize")
            ->expect(mutable: ["/p7"], result: [])
            ->set(result: ["#2"]);

        $structure->normalize($metadata);
        $this->validate($metadata)
            ->as([
                "structure" => [
                    "stateful" => "#0",
                    "sources" => ["#1"],
                    "extendables" => ["/p8"],
                    "mappings" => [
                        "/p5/p6" => ":package/id/sub/dir",
                    ],
                    "mutables" => ["#2"]
                ]
            ]);
    }
}