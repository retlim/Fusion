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

namespace Valvoid\Fusion\Tests\Units\Log\Serializers\Streams\JSON\Config;

use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Log\Serializers\Streams\JSON\Config\Normalizer;
use Valvoid\Reflex\Test\Wrapper;

class NormalizerTest extends Wrapper
{
    public function testDefaultNormalization(): void
    {
        $normalizer = new Normalizer;
        $config = [];

        $normalizer->normalize([], $config);

        $this->validate($config)
            ->as(["threshold" => Level::INFO]);
    }

    public function testCustomNormalization(): void
    {
        $normalizer = new Normalizer;
        $config = ["threshold" => Level::WARNING];

        $normalizer->normalize([], $config);

        $this->validate($config)
            ->as(["threshold" => Level::WARNING]);
    }
}