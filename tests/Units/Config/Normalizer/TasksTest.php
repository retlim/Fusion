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
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Config\Config;
use Valvoid\Fusion\Config\Normalizer;
use Valvoid\Fusion\Config\Normalizer\Tasks;
use Valvoid\Reflex\Test\Wrapper;

class TasksTest extends Wrapper
{
    public function testDefaultTask(): void
    {
        $box = $this->createStub(Box::class);
        $configuration = $this->createStub(Config::class);
        $bus = $this->createStub(Bus::class);
        $tasks = new Tasks($box, $configuration, $bus);

        $config["test"] = "#";

        $tasks->normalize($config);

        $this->validate($config)
            ->as(["test" => [
                "task" => "#"
            ]]);
    }

    public function testDefaultGroupTask(): void
    {
        $box = $this->createStub(Box::class);
        $configuration = $this->createMock(Config::class);
        $bus = $this->createStub(Bus::class);
        $tasks = new Tasks($box, $configuration, $bus);

        $configuration->fake("get")
            ->expect(breadcrumb: ["tasks", "g", "task"])
            ->return(false);

        $config["g"]["test"] = "#";

        $tasks->normalize($config);

        $this->validate($config)
            ->as(["g" => ["test" => [
                "task" => "#"
            ]]]);
    }

    public function testConfiguredTask(): void
    {
        $box = $this->createMock(Box::class);
        $configuration = $this->createMock(Config::class);
        $bus = $this->createStub(Bus::class);
        $normalizer = $this->createMock(Normalizer::class);
        $tasks = new Tasks($box, $configuration, $bus);

        $configuration->fake("hasLazy")
            ->expect(class: "#0\\Config\\Normalizer")
            ->return(true);

        $box->fake("get")
            ->expect(class: "#0\\Config\\Normalizer")
            ->return($normalizer);

        $config = [
            "test" => [
                "task" => "#0\\#1",
                "whatever"
            ]
        ];

        $normalizer->fake("normalize")
            ->set(config: "###")
            ->expect(breadcrumb: ["tasks", "test"], config: [
                "task" => "#0\\#1",
                "whatever"
            ]);

        $tasks->normalize($config);

        $this->validate($config)
            ->as(["test" => "###"]);
    }


    public function testConfiguredGroupTask(): void
    {
        $box = $this->createMock(Box::class);
        $configuration = $this->createMock(Config::class);
        $bus = $this->createStub(Bus::class);
        $normalizer = $this->createMock(Normalizer::class);
        $tasks = new Tasks($box, $configuration, $bus);

        $configuration->fake("hasLazy")
            ->expect(class: "#0\\Config\\Normalizer")
            ->return(true)
            ->fake("get")
            ->expect(breadcrumb: ["tasks", "g", "task"])
            ->return(false);

        $box->fake("get")
            ->expect(class: "#0\\Config\\Normalizer")
            ->return($normalizer);

        $config = ["g" => [
            "test" => [
                "task" => "#0\\#1",
                "whatever"
            ]
        ]];

        $normalizer->fake("normalize")
            ->set(config: "###")
            ->expect(breadcrumb: ["tasks", "g", "test"], config: [
                "task" => "#0\\#1",
                "whatever"
            ]);

        $tasks->normalize($config);

        $this->validate($config)
            ->as(["g" => ["test" => "###"]]);
    }

    public function testAnonymousTask(): void
    {
        $box = $this->createMock(Box::class);
        $configuration = $this->createMock(Config::class);
        $bus = $this->createStub(Bus::class);
        $normalizer = $this->createMock(Normalizer::class);
        $tasks = new Tasks($box, $configuration, $bus);

        $configuration->fake("get")
            ->expect(breadcrumb: ["tasks", "test", "task"])
            ->return("#0\\#1")
            ->fake("hasLazy")
            ->expect(class: "#0\\Config\\Normalizer")
            ->return(true);

        $box->fake("get")
            ->expect(class: "#0\\Config\\Normalizer")
            ->return($normalizer);

        $config = [
            "test" => [
                #"task" => "#0\\#1",
                "whatever"
            ]
        ];

        $normalizer->fake("normalize")
            ->set(config: "###")
            ->expect(breadcrumb: ["tasks", "test"], config: [
               # "task" => "#0\\#1",
                "whatever"
            ]);

        $tasks->normalize($config);

        $this->validate($config)
            ->as(["test" => "###"]);
    }

    public function testAnonymousGroupTask(): void
    {
        $box = $this->createMock(Box::class);
        $configuration = $this->createMock(Config::class);
        $bus = $this->createStub(Bus::class);
        $normalizer = $this->createMock(Normalizer::class);
        $tasks = new Tasks($box, $configuration, $bus);

        $configuration->fake("get")
            ->expect(breadcrumb: ["tasks", "g", "task"])
            ->return(false)
            ->expect(breadcrumb: ["tasks", "g", "test", "task"])
            ->return("#0\\#1")
            ->fake("hasLazy")
            ->expect(class: "#0\\Config\\Normalizer")
            ->return(true);

        $box->fake("get")
            ->expect(class: "#0\\Config\\Normalizer")
            ->return($normalizer);

        $config = [
            "g" => [
                "test" => [
                    #"task" => "#0\\#1",
                    "whatever"
                ]
            ]
        ];

        $normalizer->fake("normalize")
            ->set(config: "###")
            ->expect(breadcrumb: ["tasks", "g", "test"], config: [
                # "task" => "#0\\#1",
                "whatever"
            ]);

        $tasks->normalize($config);

        $this->validate($config)
            ->as(["g" => ["test" => "###"]]);
    }
}