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

namespace Valvoid\Fusion\Tests\Config\Normalizer\Tasks;

use Throwable;
use Valvoid\Fusion\Config\Normalizer\Tasks;
use Valvoid\Fusion\Tests\Config\Normalizer\Tasks\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Config\Normalizer\Tasks\Mocks\BusMock;
use Valvoid\Fusion\Tests\Config\Normalizer\Tasks\Mocks\ConfigMock;
use Valvoid\Fusion\Tests\Config\Normalizer\Tasks\Mocks\NormalizerMock;
use Valvoid\Fusion\Tests\Test;

class TasksTest extends Test
{
    protected string|array $coverage = Tasks::class;
    private BoxMock $box;

    public function __construct()
    {
        $this->box = new BoxMock;

        $this->testDefaultTaskConfig();
        $this->testGroupedDefaultTaskConfig();
        $this->testConfiguredTaskConfig();
        $this->testGroupedConfiguredTaskConfig();
        $this->testConfiguredParsableTaskConfig();
        $this->testGroupedConfiguredParsableTaskConfig();

        $this->box::unsetInstance();
    }

    public function testGroupedDefaultTaskConfig(): void
    {
        try {
            $configuration = new ConfigMock;
            $get = [];
            $configuration->get = function(...$breadcrumb) use (&$get) {
                $get = [...$breadcrumb];

                return false;
            };
            $tasks = new Tasks(
                box: $this->box,
                config: $configuration,
                bus: new BusMock
            );

            $config["g"]["test"] = "#";

            $tasks->normalize($config);

            if ($get != ["tasks", "g", "task"] ||
                $config !== [
                    "g" => [
                        "test" => [
                            "task" => "#"
                        ]]])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testDefaultTaskConfig(): void
    {
        try {
            $tasks = new Tasks(
                box: $this->box,
                config: new ConfigMock,
                bus: new BusMock
            );

            $config["test"] = "#";

            $tasks->normalize($config);

            if ($config !== [
                    "test" => [
                        "task" => "#"
                    ]])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testConfiguredTaskConfig(): void
    {
        try {
            $configuration = new ConfigMock;
            $has = [];
            $configuration->has = function($class) use (&$has) {
                $has[] = $class;

                return false;
            };

            $tasks = new Tasks(
                box: $this->box,
                config: $configuration,
                bus: new BusMock
            );

            $config = [
                "test" => [
                    "task" => "#0\\#1\\#2",
                    "whatever"
                ]
            ];

            $tasks->normalize($config);

            // has no normalizer
            if ($has != ["#0\\#1\\Config\\Normalizer"] ||
                $config != [
                    "test" => [
                        "task" => "#0\\#1\\#2",
                        "whatever"
                    ]
                ])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testGroupedConfiguredTaskConfig(): void
    {
        try {
            $configuration = new ConfigMock;
            $has =
            $get = [];
            $configuration->has = function($class) use (&$has) {
                $has[] = $class;

                return false;
            };

            $configuration->get = function(...$class) use (&$get) {
                $get = $class;

                return false;
            };

            $tasks = new Tasks(
                box: $this->box,
                config: $configuration,
                bus: new BusMock
            );

            $config["g"] = [
                "test" => [
                    "task" => "#0\\#1\\#2",
                    "whatever"
                ]
            ];

            $tasks->normalize($config);

            // has no normalizer
            if ($has != ["#0\\#1\\Config\\Normalizer"] ||
                $get != ["tasks", "g", "task"] ||
                $config != [
                    "g" => ["test" => [
                        "task" => "#0\\#1\\#2",
                        "whatever"
                    ]]
                ])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testGroupedConfiguredParsableTaskConfig(): void
    {
        try {
            $configuration = new ConfigMock;
            $has =
            $get =
            $boxGet = [];
            $configuration->has = function($class) use (&$has) {
                $has[] = $class;

                return true;
            };

            $configuration->get = function(...$breadcrumb) use (&$get) {
                $get = [...$breadcrumb];

                return false;
            };
            $this->box->get = function ($class) use (&$boxGet) {
                $boxGet[] = $class;

                return new NormalizerMock;
            };

            $tasks = new Tasks(
                box: $this->box,
                config: $configuration,
                bus: new BusMock
            );

            $config["g"] = [
                "test" => [
                    "task" => "#0\\#1\\#2",
                    "whatever"
                ]
            ];

            $tasks->normalize($config);

            // has normalizer
            if ($has != ["#0\\#1\\Config\\Normalizer"] ||
                $get != ["tasks", "g", "task"] ||
                $boxGet != ["#0\\#1\\Config\\Normalizer"] ||
                $config != [
                    "g" => [
                        "test" => [
                            "breadcrumb" => ["tasks", "g", "test"],
                            "config" => [
                                "task" => "#0\\#1\\#2",
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

    public function testConfiguredParsableTaskConfig(): void
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

                return new NormalizerMock;
            };

            $tasks = new Tasks(
                box: $this->box,
                config: $configuration,
                bus: new BusMock
            );

            $config = [
                "test" => [
                    "task" => "#0\\#1\\#2",
                    "whatever"
                ]
            ];

            $tasks->normalize($config);

            // has normalizer
            if ($has != ["#0\\#1\\Config\\Normalizer"] ||
                $get != ["#0\\#1\\Config\\Normalizer"] ||
                $config != [
                    "test" => [
                        "breadcrumb" => ["tasks", "test"],
                        "config" => [
                            "task" => "#0\\#1\\#2",
                            "whatever"
                        ]
                    ]
                ])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }
}