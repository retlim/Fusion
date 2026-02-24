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
use Valvoid\Fusion\Config\Normalizer\Hub;
use Valvoid\Reflex\Test\Wrapper;

class HubTest extends Wrapper
{
    public function testDefaultApi(): void
    {
        $box = $this->createStub(Box::class);
        $configuration = $this->createStub(Config::class);
        $bus = $this->createStub(Bus::class);
        $hub = new Hub($box, $configuration, $bus);

        $config["apis"]["test"] = "#";

        $hub->normalize($config);

        $this->validate($config)->as([
            "apis" => [
                "test" => [
                    "api" => "#"
                ]]]);
    }

    public function testConfiguredApi(): void
    {
        $box = $this->createMock(Box::class);
        $configuration = $this->createMock(Config::class);
        $bus = $this->createStub(Bus::class);
        $normalizer = $this->createMock(Normalizer::class);
        $hub = new Hub($box, $configuration, $bus);

        $configuration->fake("hasLazy")
            ->expect(class: "#0\\Config\\Normalizer")
            ->return(true);

        $box->fake("get")
            ->expect(class: "#0\\Config\\Normalizer")
            ->return($normalizer);

        $config = [
            "apis" => [
                "test" => [
                    "api" => "#0\\#1",
                    "whatever"
                ]
            ]
        ];

        $normalizer->fake("normalize")
            ->set(config: "###")
            ->expect(breadcrumb: ["hub", "apis", "test"], config: [
                "api" => "#0\\#1",
                "whatever"
            ]);

        $hub->normalize($config);

        $this->validate($config)
            ->as(["apis" => [
                "test" => "###"
            ]]);
    }

    public function testAnonymousApi(): void
    {
        $box = $this->createMock(Box::class);
        $configuration = $this->createMock(Config::class);
        $bus = $this->createStub(Bus::class);
        $normalizer = $this->createMock(Normalizer::class);
        $hub = new Hub($box, $configuration, $bus);

        $configuration->fake("get")
            ->expect(breadcrumb: ["hub", "apis", "test", "api"])
            ->return("#0\\#1")
            ->fake("hasLazy")
            ->expect(class: "#0\\Config\\Normalizer")
            ->return(true);

        $box->fake("get")
            ->expect(class: "#0\\Config\\Normalizer")
            ->return($normalizer);

        $config = [
            "apis" => [
                "test" => [
                    #"api" => "#0\\#1",
                    "whatever"
                ]
            ]
        ];

        $normalizer->fake("normalize")
            ->set(config: "###")
            ->expect(breadcrumb: ["hub", "apis", "test"], config: [
               # "api" => "#0\\#1",
                "whatever"
            ]);

        $hub->normalize($config);

        $this->validate($config)
            ->as(["apis" => [
                "test" => "###"
            ]]);
    }
}