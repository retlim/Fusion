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

namespace Valvoid\Fusion\Tests\Units\Config\Normalizer;

use Valvoid\Box\Box;
use Valvoid\Fusion\Config\Normalizer\Normalizer;
use Valvoid\Fusion\Config\Normalizer\Hub;
use Valvoid\Fusion\Config\Normalizer\Log;
use Valvoid\Fusion\Config\Normalizer\Tasks;
use Valvoid\Reflex\Test\Wrapper;

class NormalizerTest extends Wrapper
{
    public function testOverlay(): void
    {
        $box = $this->createStub(Box::class);
        $normalizer = new Normalizer($box);
        $config = ["#0" => [
            "#1" => "#2",
            "#3" => "#4"
        ]];

        $normalizer->overlay($config, ["#0" => [
            "#3" => "#5"
        ]]);

        $this->validate($config)->as(["#0" => [
            "#1" => "#2",
            "#3" => "#5"
        ]]);
    }

    public function testNormalize(): void
    {
        $box = $this->createMock(Box::class);
        $hub = $this->createMock(Hub::class);
        $log = $this->createMock(Log::class);
        $tasks = $this->createMock(Tasks::class);

        $box->fake("get")
            ->expect(class: Tasks::class)
            ->return($tasks)
            ->expect(class: Hub::class)
            ->return($hub)
            ->expect(class: Log::class)
            ->return($log);

        $tasks->fake("normalize")
            ->set(config: 2);

        $log->fake("normalize")
            ->set(config: 1);

        $hub->fake("normalize")
            ->set(config: 0);

        $normalizer = new Normalizer($box);
        $config = [
            "#" => null,
            "tasks" => [],
            "hub" => [
                "#" => null,
            ],
            "log" => []
        ];

        $normalizer->normalize($config);
        $this->validate($config)
            ->as([
                "tasks" => 2,
                "hub" => 0,
                "log" => 1
            ]);
    }
}