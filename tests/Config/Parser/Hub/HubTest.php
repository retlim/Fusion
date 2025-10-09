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

namespace Valvoid\Fusion\Tests\Config\Parser\Hub;

use Throwable;
use Valvoid\Fusion\Config\Parser\Hub;
use Valvoid\Fusion\Tests\Config\Parser\Hub\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Config\Parser\Hub\Mocks\BusMock;
use Valvoid\Fusion\Tests\Config\Parser\Hub\Mocks\ConfigMock;
use Valvoid\Fusion\Tests\Config\Parser\Hub\Mocks\ParserMock;
use Valvoid\Fusion\Tests\Test;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class HubTest extends Test
{
    protected string|array $coverage = Hub::class;
    private BoxMock $box;

    public function __construct()
    {
        $this->box = new BoxMock;

        $this->testDefaultApiConfig();
        $this->testConfiguredApiConfig();
        $this->testConfiguredParsableApiConfig();

        $this->box::unsetInstance();
    }

    public function testDefaultApiConfig(): void
    {
        try {
            $hub = new Hub(
                box: $this->box,
                config: new ConfigMock,
                bus: new BusMock
            );

            $config["apis"]["test"] = "#";

            $hub->parse($config);

            if ($config !== [
                "apis" => [
                    "test" => [
                        "api" => "#"
                    ]]])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testConfiguredApiConfig(): void
    {
        try {
            $configuration = new ConfigMock;
            $has = [];
            $configuration->has = function($class) use (&$has) {
                $has[] = $class;

                return false;
            };

            $hub = new Hub(
                box: $this->box,
                config: $configuration,
                bus: new BusMock
            );

            $config = [
                "apis" => [
                    "test" => [
                        "api" => "#0\\#1\\#2",
                        "whatever"
                    ]
                ]
            ];

            $hub->parse($config);

            // has no parser
            if ($has != ["#0\\#1\\Config\\Parser"] ||
                $config != [
                    "apis" => [
                        "test" => [
                            "api" => "#0\\#1\\#2",
                            "whatever"
                        ]
                    ]
                ])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testConfiguredParsableApiConfig(): void
    {
        try {
            $configuration = new ConfigMock;
            $has =
            $get = [];
            $configuration->has = function($class) use (&$has) {
                $has[] = $class;

                return true;
            };

            $this->box->get = function ($class) use (&$get) {
                $get[] = $class;

                return new ParserMock;
            };

            $hub = new Hub(
                box: $this->box,
                config: $configuration,
                bus: new BusMock
            );

            $config = [
                "apis" => [
                    "test" => [
                        "api" => "#0\\#1\\#2",
                        "whatever"
                    ]
                ]
            ];

            $hub->parse($config);

            // has parser
            if ($has != ["#0\\#1\\Config\\Parser"] ||
                $get != ["#0\\#1\\Config\\Parser"] ||
                $config != [
                    "apis" => [
                        "test" => [
                            "breadcrumb" => ["hub", "apis", "test"],
                            "config" => [
                                "api" => "#0\\#1\\#2",
                                "whatever"
                            ]
                        ]
                    ]
                ])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }
}