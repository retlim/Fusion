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
use Valvoid\Fusion\Metadata\Normalizer\Normalizer;
use Valvoid\Fusion\Metadata\Normalizer\Structure;
use Valvoid\Reflex\Test\Wrapper;

class NormalizerTest extends Wrapper
{
    public function testNormalize(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $structure = $this->createMock(Structure::class);
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

        $box->fake("get")
            ->expect(class: Structure::class)
            ->return($structure);

        $structure->fake("normalize")
            ->set(meta: [
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
            ]);

        $normalizer = new Normalizer(
            box: $box,
            bus: $bus
        );

        $normalizer->normalize($metadata);

        $this->validate($metadata)->as([
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
            ]);
    }

    public function testOverlay(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $content = [
            "key1" => "value1",
            "whatever"
        ];

        $normalizer = new Normalizer(
            box: $box,
            bus: $bus
        );

        $normalizer->overlay($content, ["key1" => null, "key2" => "value2"]);
        $this->validate($content)->as([
                "key1" => null,
                "whatever",
                "key2" => "value2"
            ]);
    }
}