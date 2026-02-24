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
use Valvoid\Fusion\Config\Normalizer\Log;
use Valvoid\Reflex\Test\Wrapper;

class LogTest extends Wrapper
{
    public function testDefaultSerializer(): void
    {
        $box = $this->createStub(Box::class);
        $configuration = $this->createStub(Config::class);
        $bus = $this->createStub(Bus::class);
        $log = new Log($box, $configuration, $bus);

        $config["serializers"]["test"] = "#";

        $log->normalize($config);

        $this->validate($config)
            ->as(["serializers" => [
                    "test" => [
                        "serializer" => "#"
                    ]]]);
    }

    public function testConfiguredSerializer(): void
    {
        $box = $this->createMock(Box::class);
        $configuration = $this->createMock(Config::class);
        $bus = $this->createStub(Bus::class);
        $normalizer = $this->createMock(Normalizer::class);
        $log = new Log($box, $configuration, $bus);

        $configuration->fake("hasLazy")
            ->expect(class: "#0\\Config\\Normalizer")
            ->return(true);

        $box->fake("get")
            ->expect(class: "#0\\Config\\Normalizer")
            ->return($normalizer);

        $config = [
            "serializers" => [
                "test" => [
                    "serializer" => "#0\\#1",
                    "whatever"
                ]
            ]
        ];

        $normalizer->fake("normalize")
            ->set(config: "###")
            ->expect(breadcrumb: ["log", "serializers", "test"], config: [
                "serializer" => "#0\\#1",
                "whatever"
            ]);

        $log->normalize($config);

        $this->validate($config)
            ->as(["serializers" => [
                "test" => "###"
            ]]);
    }

    public function testAnonymousSerializer(): void
    {
        $box = $this->createMock(Box::class);
        $configuration = $this->createMock(Config::class);
        $bus = $this->createStub(Bus::class);
        $normalizer = $this->createMock(Normalizer::class);
        $log = new Log($box, $configuration, $bus);

        $configuration->fake("get")
            ->expect(breadcrumb: ["log", "serializers", "test", "serializer"])
            ->return("#0\\#1")
            ->fake("hasLazy")
            ->expect(class: "#0\\Config\\Normalizer")
            ->return(true);

        $box->fake("get")
            ->expect(class: "#0\\Config\\Normalizer")
            ->return($normalizer);

        $config = [
            "serializers" => [
                "test" => [
                    #"serializer" => "#0\\#1",
                    "whatever"
                ]
            ]
        ];

        $normalizer->fake("normalize")
            ->set(config: "###")
            ->expect(breadcrumb: ["log", "serializers", "test"], config: [
               # "serializer" => "#0\\#1",
                "whatever"
            ]);

        $log->normalize($config);

        $this->validate($config)
            ->as(["serializers" => [
                "test" => "###"
            ]]);
    }
}