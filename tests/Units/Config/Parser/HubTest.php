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

namespace Valvoid\Fusion\Tests\Units\Config\Parser;

use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Config\Config;
use Valvoid\Fusion\Config\Parser;
use Valvoid\Fusion\Config\Parser\Hub;
use Valvoid\Reflex\Test\Wrapper;

class HubTest extends Wrapper
{
   public function testDefaultApi(): void
   {
       $box = $this->createStub(Box::class);
       $bus = $this->createStub(Bus::class);
       $configuration = $this->createStub(Config::class);
       $hub = new Hub(
           box: $box,
           config: $configuration,
           bus: $bus);

       $config["apis"]["test"] = "#";

       $hub->parse($config);
       $this->validate($config)
           ->as(["apis" => [
               "test" => [
                   "api" => "#"
               ]]]);
   }

    public function testConfiguredApi(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createStub(Bus::class);
        $configuration = $this->createMock(Config::class);
        $parser = $this->createMock(Parser::class);
        $hub = new Hub(
            box: $box,
            config: $configuration,
            bus: $bus);

        $configuration->fake("hasLazy")
            ->expect(class: "#0\\Config\\Parser")
            ->return(true);

        $box->fake("get")
            ->expect(class: "#0\\Config\\Parser")
            ->return($parser);

        $config["apis"]["test"] = [
            "api" => "#0\\#1",
            "whatever"
        ];

        $parser->fake("parse")
            ->expect(breadcrumb: ["hub", "apis", "test"],
                config: $config["apis"]["test"])
            ->set(config: "###");

        $hub->parse($config);
        $this->validate($config["apis"]["test"])
            ->as("###");
    }

    public function testTestAnonymousApi(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createStub(Bus::class);
        $configuration = $this->createMock(Config::class);
        $parser = $this->createMock(Parser::class);
        $hub = new Hub(
            box: $box,
            config: $configuration,
            bus: $bus);

        $configuration->fake("hasLazy")
            ->expect(class: "#0\\Config\\Parser")
            ->return(true)
            ->fake("get")
            ->expect(breadcrumb: ["hub", "apis", "test", "api"])
            ->return("#0\\#1");

        $box->fake("get")
            ->expect(class: "#0\\Config\\Parser")
            ->return($parser);

        $config["apis"]["test"] = [
            #"api" => "#0\\#1",
            "whatever"
        ];

        $parser->fake("parse")
            ->expect(breadcrumb: ["hub", "apis", "test"],
                config: $config["apis"]["test"])
            ->set(config: "###");

        $hub->parse($config);
        $this->validate($config["apis"]["test"])
            ->as("###");
    }
}