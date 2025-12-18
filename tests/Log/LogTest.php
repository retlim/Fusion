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

namespace Valvoid\Fusion\Tests\Log;

use Valvoid\Fusion\Log\Events\Errors\Config;
use Valvoid\Fusion\Log\Events\Errors\Deadlock;
use Valvoid\Fusion\Log\Events\Errors\Environment;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Errors\Lifecycle;
use Valvoid\Fusion\Log\Events\Errors\Metadata;
use Valvoid\Fusion\Log\Events\Errors\Request;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Events\Infos\Error as InfoError;
use Valvoid\Fusion\Log\Events\Infos\Id;
use Valvoid\Fusion\Log\Events\Infos\Name;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Tests\Log\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Log\Mocks\ConfigMock;
use Valvoid\Fusion\Tests\Log\Mocks\InterceptorMock;
use Valvoid\Fusion\Tests\Log\Mocks\SerializerMock;
use Valvoid\Fusion\Tests\Test;

class LogTest extends Test
{
    protected string|array $coverage = [
        Log::class,

        // ballast
        Name::class,
        Id::class,
        InfoError::class,
        Content::class,
        Config::class,
        Deadlock::class,
        Environment::class,
        Error::class,
        Lifecycle::class,
        Metadata::class,
        Request::class
    ];

    private BoxMock $box;
    private ConfigMock $config;
    private InterceptorMock $interceptor;
    private Log $log;
    private SerializerMock $serializer;

    public function __construct()
    {
        $this->box = new BoxMock;
        $this->interceptor = new InterceptorMock;
        $this->config = new ConfigMock;
        $this->serializer = new SerializerMock([]);

        $this->box->get = function (string $class, ...$args) {
            if ($class == "Valvoid\Fusion\Tests\Log\Mocks\SerializerMock")
                return $this->serializer;
        };

        $this->config->get = function (...$breadcrumb) {
            if ($breadcrumb == ["log", "serializers"])
                return ["test" => ["serializer" => SerializerMock::class]];

            $this->handleFailedTest();
        };

        $this->log = new Log($this->box, $this->config);

        $this->testInterceptor();
        $this->testError();
        $this->testWarning();
        $this->testNotice();
        $this->testInfo();
        $this->testVerbose();
        $this->testDebug();

        $this->box::unsetInstance();
    }

    public function testInterceptor(): void
    {
        $level =
        $event =
        $interceptor = null;

        $this->log->addInterceptor($this->interceptor);
        $this->interceptor->extend = function ($event) use (&$interceptor) {
            $interceptor = $event;
        };

        $this->serializer->log = function ($l, $e) use (&$level, &$event) {
            $level = $l;
            $event = $e;
        };

        $this->log->error("error");

        if ($level !== Level::ERROR ||
            $event !== "error" ||
            $interceptor !== "error")
            $this->handleFailedTest();
    }

    public function testError(): void
    {
        $level =
        $event = null;

        $this->serializer->log = function ($l, $e) use (&$level, &$event) {
            $level = $l;
            $event = $e;
        };

        $this->log->error("error");

        if ($level !== Level::ERROR ||
            $event !== "error")
            $this->handleFailedTest();
    }

    public function testWarning(): void
    {
        $level =
        $event = null;

        $this->serializer->log = function ($l, $e) use (&$level, &$event) {
            $level = $l;
            $event = $e;
        };

        $this->log->warning("warning");

        if ($level !== Level::WARNING ||
            $event !== "warning")
            $this->handleFailedTest();
    }

    public function testNotice(): void
    {
        $level =
        $event = null;

        $this->serializer->log = function ($l, $e) use (&$level, &$event) {
            $level = $l;
            $event = $e;
        };

        $this->log->notice("notice");

        if ($level !== Level::NOTICE ||
            $event !== "notice")
            $this->handleFailedTest();
    }

    public function testInfo(): void
    {
        $level =
        $event = null;

        $this->serializer->log = function ($l, $e) use (&$level, &$event) {
            $level = $l;
            $event = $e;
        };

        $this->log->info("info");

        if ($level !== Level::INFO ||
            $event !== "info")
            $this->handleFailedTest();
    }

    public function testVerbose(): void
    {
        $level =
        $event = null;

        $this->serializer->log = function ($l, $e) use (&$level, &$event) {
            $level = $l;
            $event = $e;
        };

        $this->log->verbose("verbose");

        if ($level !== Level::VERBOSE ||
            $event !== "verbose")
            $this->handleFailedTest();
    }

    public function testDebug(): void
    {
        $level =
        $event = null;

        $this->serializer->log = function ($l, $e) use (&$level, &$event) {
            $level = $l;
            $event = $e;
        };

        $this->log->debug("debug");

        if ($level !== Level::DEBUG ||
            $event !== "debug")
            $this->handleFailedTest();
    }
}