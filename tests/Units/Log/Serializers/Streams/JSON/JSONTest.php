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

namespace Valvoid\Fusion\Tests\Units\Log\Serializers\Streams\JSON;

use Valvoid\Fusion\Log\Events\Errors\Config;
use Valvoid\Fusion\Log\Events\Errors\Deadlock;
use Valvoid\Fusion\Log\Events\Errors\Environment;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Errors\Lifecycle;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Events\Infos\Error as InfoError;
use Valvoid\Fusion\Log\Events\Errors\Metadata;
use Valvoid\Fusion\Log\Events\Errors\Request;
use Valvoid\Fusion\Log\Events\Infos\Id;
use Valvoid\Fusion\Log\Events\Infos\Name;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Log\Serializers\Streams\JSON\JSON;
use Valvoid\Reflex\Test\Wrapper;

class JSONTest extends Wrapper
{
    private JSON $serializer;

    public function init(): void
    {
        parent::init();

        $this->serializer = new JSON([
            "threshold" => Level::VERBOSE
        ]);
    }

    public function testMessage(): void
    {
        ob_start();
        $this->serializer->log(Level::INFO, "###");

        $this->validate(ob_get_clean())
            ->as(json_encode([
                "category" => "generic",
                "type" => "string",
                "payload" => [
                    "message" => "###"
                ],
                "level" => [
                    "name" => "info",
                    "ordinal" => Level::INFO->value
                ]
            ]));
    }

    public function testDeadlock(): void
    {
        $deadlock = $this->createStub(Deadlock::class);

        $deadlock->fake("getLockedPath")
            ->return(["#3"])
            ->fake("getConflictPath")
            ->return(["#4"])
            ->fake("getLockedLayer")
            ->return("#5")
            ->fake("getConflictLayer")
            ->return("#6")
            ->fake("getLockedBreadcrumb")
            ->return(["#7"])
            ->fake("getConflictBreadcrumb")
            ->return(["#8"]);

        ob_start();
        $this->serializer->log(Level::INFO, $deadlock);

        $this->validate(ob_get_clean())
            ->as(json_encode([
                "category" => "error",
                "type" => "deadlock",
                "payload" => [
                    "message" => "",
                    "paths" => [
                        "built" => ["#3"],
                        "conflict" => ["#4"]
                    ],
                    "layers" => [
                        "built" => "#5",
                        "conflict" => "#6"
                    ],
                    "breadcrumbs" => [
                        "built" => ["#7"],
                        "conflict" => ["#8"]
                    ]
                ],
                "level" => [
                    "name" => "info",
                    "ordinal" => Level::INFO->value
                ]
            ]));
    }

    public function testEnvironment(): void
    {
        $environment = $this->createStub(Environment::class);

        $environment->fake("getPath")
            ->return(["#3"])
            ->fake("getLayer")
            ->return("#4")
            ->fake("getBreadcrumb")
            ->return(["#5"]);

        ob_start();
        $this->serializer->log(Level::INFO, $environment);
        $this->validate(ob_get_clean())
            ->as(json_encode([
                "category" => "error",
                "type" => "environment",
                "payload" => [
                    "path" => ["#3"],
                    "layer" => "#4",
                    "breadcrumb" => ["#5"],
                    "message" => ""
                ],
                "level" => [
                    "name" => "info",
                    "ordinal" => Level::INFO->value
                ]
            ]));
    }

    public function testMetadata(): void
    {
        $metadata = $this->createStub(Metadata::class);

        $metadata->fake("getPath")
            ->return(["#3"])
            ->fake("getLayer")
            ->return("#4")
            ->fake("getBreadcrumb")
            ->return(["#5"]);

        ob_start();
        $this->serializer->log(Level::INFO, $metadata);
        $this->validate(ob_get_clean())
            ->as(json_encode([
                "category" => "error",
                "type" => "metadata",
                "payload" => [
                    "path" => ["#3"],
                    "layer" => "#4",
                    "breadcrumb" => ["#5"],
                    "message" => ""
                ],
                "level" => [
                    "name" => "info",
                    "ordinal" => Level::INFO->value
                ]
            ]));
    }

    public function testRequest(): void
    {
        $request = $this->createStub(Request::class);

        $request->fake("getPath")
            ->return(["#3"])
            ->fake("getSources")
            ->return(["#4"]);

        ob_start();
        $this->serializer->log(Level::INFO, $request);
        $this->validate(ob_get_clean())
            ->as(json_encode([
                "category" => "error",
                "type" => "request",
                "payload" => [
                    "path" => ["#3"],
                    "sources" => ["#4"],
                    "code" => 0,
                    "message" => ""
                ],
                "level" => [
                    "name" => "info",
                    "ordinal" => Level::INFO->value
                ]
            ]));
    }

    public function testError(): void
    {
        $error = $this->createStub(Error::class);

        ob_start();
        $this->serializer->log(Level::INFO, $error);
        $this->validate(ob_get_clean())
            ->as(json_encode([
                "category" => "error",
                "type" => "error",
                "payload" => [
                    "trace" => $error->getTrace(),
                    "line" => $error->getLine(),
                    "file" => $error->getFile(),
                    "message" => $error->getMessage()
                ],
                "level" => [
                    "name" => "info",
                    "ordinal" => Level::INFO->value]
            ]));
    }

    public function testErrorInfo(): void
    {
        $error = $this->createStub(InfoError::class);

        $error->fake("getPath")
            ->return(["#3"])
            ->fake("getCode")
            ->return(11)
            ->fake("getMessage")
            ->return("#4");

        ob_start();
        $this->serializer->log(Level::INFO, $error);
        $this->validate(ob_get_clean())
            ->as(json_encode([
                "category" => "info",
                "type" => "error",
                "payload" => [
                    "path" => ["#3"],
                    "code" => 11,
                    "message" => "#4"
                ],
                "level" => [
                    "name" => "info",
                    "ordinal" => Level::INFO->value]
            ]));
    }

    public function testContent(): void
    {
        $content = $this->createStub(Content::class);

        $content->fake("getId")
            ->return("#3")
            ->fake("getVersion")
            ->return("#4")
            ->fake("getName")
            ->return("#5")
            ->fake("getDescription")
            ->return("#6")
            ->fake("getSource")
            ->return("#7")
            ->fake("getDir")
            ->return("#8");

        ob_start();
        $this->serializer->log(Level::INFO, $content);
        $this->validate(ob_get_clean())
            ->as(json_encode([
                "category" => "info",
                "type" => "content",
                "payload" => [
                    "id" => "#3",
                    "version" => "#4",
                    "name" => "#5",
                    "description" => "#6",
                    "source" => "#7",
                    "dir" => "#8"
                ],
                "level" => [
                    "name" => "info",
                    "ordinal" => Level::INFO->value]
            ]));
    }

    public function testLifecycle(): void
    {
        $lifecycle = $this->createStub(Lifecycle::class);

        $lifecycle->fake("getPath")
            ->return(["#3"])
            ->fake("getLayer")
            ->return("#4")
            ->fake("getBreadcrumb")
            ->return(["#5"]);

        ob_start();
        $this->serializer->log(Level::INFO, $lifecycle);
        $this->validate(ob_get_clean())
            ->as(json_encode([
                "category" => "error",
                "type" => "lifecycle",
                "payload" => [
                    "path" => ["#3"],
                    "layer" => "#4",
                    "breadcrumb" => ["#5"],
                    "message" => ""
                ],
                "level" => [
                    "name" => "info",
                    "ordinal" => Level::INFO->value]
            ]));
    }

    public function testConfig(): void
    {
        $config = $this->createStub(Config::class);

        $config->fake("getLayer")
            ->return("#3")
            ->fake("getBreadcrumb")
            ->return(["#4"]);

        ob_start();
        $this->serializer->log(Level::INFO, $config);
        $this->validate(ob_get_clean())
            ->as(json_encode([
                "category" => "error",
                "type" => "config",
                "payload" => [
                    "layer" => "#3",
                    "breadcrumb" => ["#4"],
                    "message" => ""
                ],
                "level" => [
                    "name" => "info",
                    "ordinal" => Level::INFO->value]
            ]));
    }

    public function testId(): void
    {
        $id = $this->createStub(Id::class);

        $id->fake("getId")
            ->return("#3");

        ob_start();
        $this->serializer->log(Level::INFO, $id);
        $this->validate(ob_get_clean())
            ->as(json_encode([
                "category" => "info",
                "type" => "id",
                "payload" => [
                    "id" => "#3"
                ],
                "level" => [
                    "name" => "info",
                    "ordinal" => Level::INFO->value]
            ]));
    }

    public function testName(): void
    {
        $name = $this->createStub(Name::class);

        $name->fake("getName")
            ->return("#3");

        ob_start();
        $this->serializer->log(Level::INFO, $name);
        $this->validate(ob_get_clean())
            ->as(json_encode([
                "category" => "info",
                "type" => "name",
                "payload" => [
                    "name" => "#3"
                ],
                "level" => [
                    "name" => "info",
                    "ordinal" => Level::INFO->value]
            ]));
    }
}