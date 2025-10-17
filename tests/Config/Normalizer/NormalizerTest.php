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

namespace Valvoid\Fusion\Tests\Config\Normalizer;

use Throwable;
use Valvoid\Fusion\Config\Normalizer\Log;
use Valvoid\Fusion\Config\Normalizer\Normalizer;
use Valvoid\Fusion\Config\Normalizer\Hub;
use Valvoid\Fusion\Tests\Config\Normalizer\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Config\Normalizer\Mocks\HubNormalizerMock;
use Valvoid\Fusion\Tests\Config\Normalizer\Mocks\LogNormalizerMock;
use Valvoid\Fusion\Tests\Config\Normalizer\Mocks\TasksNormalizerMock;
use Valvoid\Fusion\Tests\Test;

class NormalizerTest extends Test
{
    protected string|array $coverage = Normalizer::class;
    private BoxMock $box;
    public function __construct()
    {
        $this->box = new BoxMock;

        $this->testNormalize();
        $this->testOverlay();

        $this->box::unsetInstance();
    }

    public function testNormalize(): void
    {
        try {
            $tasks = new TasksNormalizerMock;
            $tasks->normalize = function (&$config) {
                $config = 2;
            };

            $log = new LogNormalizerMock;
            $log->normalize = function (&$config) {
                $config = 1;
            };

            $hub = new HubNormalizerMock;
            $reset = false;

            $hub->normalize = function (&$config) use (&$reset) {
                $reset = ($config == []);

                $config = 0;
            };

            $this->box = new BoxMock;
            $this->box->get = function ($class) use ($hub, $log, $tasks) {
                if ($class == Hub::class)
                    return $hub;

                if ($class == Log::class)
                    return $log;

                return $tasks;
            };

            $normalizer = new Normalizer(box: $this->box);
            $config = [
                "#" => null,
                "tasks" => [],
                "hub" => [
                    "#" => null,
                ],
                "log" => []
            ];

            $normalizer->normalize($config);

            if (!$reset ||
                $config != [
                    "tasks" => 2,
                    "hub" => 0,
                    "log" => 1

                ]) $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testOverlay(): void
    {
        try {
            $this->box = new BoxMock;
            $normalizer = new Normalizer(box: $this->box);
            $config = ["#0" => [
                "#1" => "#2",
                "#3" => "#4"
            ]];

            $normalizer->overlay($config, ["#0" => [
                "#3" => "#5"
            ]]);

            if ($config != ["#0" => [
                "#1" => "#2",
                "#3" => "#5"
            ]]) $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }
}