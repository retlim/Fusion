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

namespace Valvoid\Fusion\Tests\Units\Log;

use Valvoid\Box\Box;
use Valvoid\Fusion\Config\Config;
use Valvoid\Fusion\Log\Events\Interceptor;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Log\Serializers\Streams\Stream;
use Valvoid\Reflex\Test\Wrapper;

class LogTest extends Wrapper
{
    public function testInterceptor(): void
    {
        $box = $this->createStub(Box::class);
        $config = $this->createMock(Config::class);
        $interceptor = $this->createMock(Interceptor::class);

        $config->fake("get")
            ->expect(breadcrumb: ["log", "serializers"])
            ->return([]);

        $interceptor->fake("extend")
            ->expect(event: "#");

        $log = new Log(
            box: $box,
            config: $config);

        $log->addInterceptor($interceptor);
        $log->error("#");
        $log->removeInterceptor();
        $log->error("#");
    }

    public function testError(): void
    {
        $box = $this->createMock(Box::class);
        $config = $this->createMock(Config::class);
        $serializer = $this->createMock(Stream::class);
        $configuration = ["serializer" => Stream::class];

        $box->fake("get")
            ->expect(class: Stream::class,
                arguments: ["configuration" => $configuration])
            ->return($serializer);

        $config->fake("get")
            ->expect(breadcrumb: ["log", "serializers"])
            ->return(["test" => ["serializer" => Stream::class]]);

        $log = new Log(
            box: $box,
            config: $config);

        $serializer->fake("log")
            ->expect(level: Level::ERROR, event: "#");

        $log->error("#");
    }

    public function testWarning(): void
    {
        $box = $this->createMock(Box::class);
        $config = $this->createMock(Config::class);
        $serializer = $this->createMock(Stream::class);
        $configuration = ["serializer" => Stream::class];

        $box->fake("get")
            ->expect(class: Stream::class,
                arguments: ["configuration" => $configuration])
            ->return($serializer);

        $config->fake("get")
            ->expect(breadcrumb: ["log", "serializers"])
            ->return(["test" => ["serializer" => Stream::class]]);

        $log = new Log(
            box: $box,
            config: $config);

        $serializer->fake("log")
            ->expect(level: Level::WARNING, event: "#");

        $log->warning("#");
    }

    public function testNotice(): void
    {
        $box = $this->createMock(Box::class);
        $config = $this->createMock(Config::class);
        $serializer = $this->createMock(Stream::class);
        $configuration = ["serializer" => Stream::class];

        $box->fake("get")
            ->expect(class: Stream::class,
                arguments: ["configuration" => $configuration])
            ->return($serializer);

        $config->fake("get")
            ->expect(breadcrumb: ["log", "serializers"])
            ->return(["test" => ["serializer" => Stream::class]]);

        $log = new Log(
            box: $box,
            config: $config);

        $serializer->fake("log")
            ->expect(level: Level::NOTICE, event: "#");

        $log->notice("#");
    }

    public function testInfo(): void
    {
        $box = $this->createMock(Box::class);
        $config = $this->createMock(Config::class);
        $serializer = $this->createMock(Stream::class);
        $configuration = ["serializer" => Stream::class];

        $box->fake("get")
            ->expect(class: Stream::class,
                arguments: ["configuration" => $configuration])
            ->return($serializer);

        $config->fake("get")
            ->expect(breadcrumb: ["log", "serializers"])
            ->return(["test" => ["serializer" => Stream::class]]);

        $log = new Log(
            box: $box,
            config: $config);

        $serializer->fake("log")
            ->expect(level: Level::INFO, event: "#");

        $log->info("#");
    }

    public function testVerbose(): void
    {
        $box = $this->createMock(Box::class);
        $config = $this->createMock(Config::class);
        $serializer = $this->createMock(Stream::class);
        $configuration = ["serializer" => Stream::class];

        $box->fake("get")
            ->expect(class: Stream::class,
                arguments: ["configuration" => $configuration])
            ->return($serializer);

        $config->fake("get")
            ->expect(breadcrumb: ["log", "serializers"])
            ->return(["test" => ["serializer" => Stream::class]]);

        $log = new Log(
            box: $box,
            config: $config);

        $serializer->fake("log")
            ->expect(level: Level::VERBOSE, event: "#");

        $log->verbose("#");
    }

    public function testDebug(): void
    {
        $box = $this->createMock(Box::class);
        $config = $this->createMock(Config::class);
        $serializer = $this->createMock(Stream::class);
        $configuration = ["serializer" => Stream::class];

        $box->fake("get")
            ->expect(class: Stream::class,
                arguments: ["configuration" => $configuration])
            ->return($serializer);

        $config->fake("get")
            ->expect(breadcrumb: ["log", "serializers"])
            ->return(["test" => ["serializer" => Stream::class]]);

        $log = new Log(
            box: $box,
            config: $config);

        $serializer->fake("log")
            ->expect(level: Level::DEBUG, event: "#");

        $log->debug("#");
    }
}