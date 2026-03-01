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

namespace Valvoid\Fusion\Tests\Units\Hub\APIs\Remote\Valvoid\Config;

use Valvoid\Fusion\Hub\APIs\Remote\Valvoid\Config\Normalizer;
use Valvoid\Reflex\Test\Wrapper;

class NormalizerTest extends Wrapper
{
    public function testDefault(): void
    {
        $config = [];
        $normalizer = new Normalizer;
        $normalizer->normalize(["hub", "apis", "valvoid.com"], $config);

        $this->validate($config)
            ->as([
                "tokens" => [],
                "protocol" => "https",
                "domain" => "valvoid.com",
                "url" => "https://api.valvoid.com/v1/registry"
            ]);
    }

    public function testCustom(): void
    {
        $config = [
            "tokens" => "c1",
            "protocol" => "c2"
        ];

        $normalizer = new Normalizer;
        $normalizer->normalize(["hub", "apis", "c3"], $config);

        $this->validate($config)
            ->as([
                "tokens" => "c1",
                "protocol" => "c2",
                "domain" => "c3",
                "url" => "c2://api.c3/v1/registry"
            ]);
    }
}