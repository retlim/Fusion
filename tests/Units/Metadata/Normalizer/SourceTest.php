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
use Valvoid\Fusion\Metadata\Normalizer\Source;
use Valvoid\Reflex\Test\Wrapper;

class SourceTest extends Wrapper
{
    public function testSources(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $sources = [];
        $source = new Source(
            box: $box,
            bus: $bus
        );

        // leading slash legacy ballast
        $source->normalize([
            ["/path1" => "/source1"],
            ["/path2" => "/source2"],
            ["/path2" => "/source3"]
        ],
            $sources);

        $this->validate($sources)->as([
            "/path1" => ["source1"],
            "/path2" => [
                "source2",
                "source3"
            ]]);
    }
}