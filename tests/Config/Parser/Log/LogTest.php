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
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Valvoid\Fusion\Tests\Config\Parser\Log;

use Throwable;
use Valvoid\Fusion\Config\Parser\Log;
use Valvoid\Fusion\Tests\Config\Parser\Log\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Config\Parser\Log\Mocks\BusMock;
use Valvoid\Fusion\Tests\Config\Parser\Log\Mocks\ConfigMock;
use Valvoid\Fusion\Tests\Config\Parser\Log\Mocks\ParserMock;
use Valvoid\Fusion\Tests\Test;

class LogTest extends Test
{
    protected string|array $coverage = Log::class;
    private BoxMock $box;

    public function __construct()
    {
        $this->box = new BoxMock;

        $this->testDefaultSerializerConfig();
        $this->testConfiguredSerializerConfig();
        $this->testConfiguredParsableSerializerConfig();

        $this->box::unsetInstance();
    }

    public function testDefaultSerializerConfig(): void
    {
        try {
            $log = new Log(
                box: $this->box,
                config: new ConfigMock,
                bus: new BusMock
            );

            $config["serializers"]["test"] = "#";

            $log->parse($config);

            $assertion = [
                "serializers" => [
                    "test" => [
                        "serializer" => "#"
                    ]
                ]
            ];

            if ($config !== $assertion)
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testConfiguredSerializerConfig(): void
    {
        try {
            $configuration = new ConfigMock;
            $has = [];
            $configuration->has = function($class) use (&$has) {
                $has[] = $class;

                return false;
            };

            $log = new Log(
                box: $this->box,
                config: $configuration,
                bus: new BusMock
            );

            $config = [
                "serializers" => [
                    "test" => [
                        "serializer" => "#0\\#1\\#2",
                        "whatever"
                    ]
                ]
            ];

            $log->parse($config);

            // has no parser
            if ($has != ["#0\\#1\\Config\\Parser"] ||
                $config != [
                    "serializers" => [
                        "test" => [
                            "serializer" => "#0\\#1\\#2",
                            "whatever"
                        ]
                    ]
                ])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testConfiguredParsableSerializerConfig(): void
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

            $log = new Log(
                box: $this->box,
                config: $configuration,
                bus: new BusMock
            );

            $config = [
                "serializers" => [
                    "test" => [
                        "serializer" => "#0\\#1\\#2",
                        "whatever"
                    ]
                ]
            ];

            $log->parse($config);

            // has parser
            if ($has != ["#0\\#1\\Config\\Parser"] ||
                $get != ["#0\\#1\\Config\\Parser"] ||
                $config != [
                    "serializers" => [
                        "test" => [
                            "breadcrumb" => ["log", "serializers", "test"],
                            "config" => [
                                "serializer" => "#0\\#1\\#2",
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