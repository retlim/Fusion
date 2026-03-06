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

namespace Valvoid\Fusion\Tests\Units\Log\Serializers\Streams\Terminal;

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
use Valvoid\Fusion\Log\Serializers\Streams\Terminal\Terminal;
use Valvoid\Reflex\Test\Wrapper;

class TerminalTest extends Wrapper
{
    private Terminal $serializer;

    public function init(): void
    {
        parent::init();

        $this->serializer = new Terminal([
            "threshold" => Level::VERBOSE
        ]);
    }

    public function testMessage(): void
    {
        ob_start();
        $this->serializer->log(Level::INFO, "###");

        $this->validate(ob_get_clean())
            ->as("\n###");
    }

    public function testDeadlock(): void
    {
        $deadlock = $this->createStub(Deadlock::class);

        $deadlock->fake("getLockedPath")
            ->return([["layer" => "#0", "breadcrumb" => ["#1"], "source" => "#2"]])
            ->fake("getConflictPath")
            ->return([["layer" => "#3", "breadcrumb" => ["#4"], "source" => "#5"]])
            ->fake("getLockedLayer")
            ->return("#6")
            ->fake("getConflictLayer")
            ->return("#7")
            ->fake("getLockedBreadcrumb")
            ->return(["#8"])
            ->fake("getConflictBreadcrumb")
            ->return(["#9"]);

        ob_start();
        $this->serializer->log(Level::INFO, $deadlock);

        $this->validate(ob_get_clean())
            ->as("\n\n\033[1;4mdeadlock info\033[0m:" .
                "\n\033[4min\033[0m: \033[0;4m#0" .
                "\n\033[0mat: #1" .
                "\nas: #2" .
                "\n\033[4min\033[0m: \033[0;4m#6" .
                "\n\033[0mat: #8" .
                "\n    ---" .
                "\n\033[4min\033[0m: \033[0;4m#3" .
                "\n\033[0mat: #4" .
                "\nas: #5" .
                "\n\033[4min\033[0m: \033[0;4m#7" .
                "\n\033[0mat: #9" .
                "\nis: ");

    }

    public function testEnvironment(): void
    {
        $environment = $this->createStub(Environment::class);

        $environment->fake("getPath")
            ->return([["layer" => "#0", "breadcrumb" => ["#1"], "source" => "#2"]])
            ->fake("getLayer")
            ->return("#3")
            ->fake("getBreadcrumb")
            ->return(["#4"]);

        ob_start();
        $this->serializer->log(Level::INFO, $environment);
        $this->validate(ob_get_clean())
            ->as("\n\n\033[1;4menvironment info\033[0m:" .
                "\n\033[4min\033[0m: \033[0;4m#0" .
                "\n\033[0mat: #1" .
                "\nas: #2" .
                "\n\033[4min\033[0m: \033[0;4m#3" .
                "\n\033[0mat: #4" .
                "\nis: ");
    }

    public function testMetadata(): void
    {
        $metadata = $this->createStub(Metadata::class);

        $metadata->fake("getPath")
            ->return([["layer" => "#0", "breadcrumb" => ["#1"], "source" => "#2"]])
            ->fake("getLayer")
            ->return("#3")
            ->fake("getBreadcrumb")
            ->return(["#4"])
            ->fake("getRow")
            ->return(33);

        ob_start();
        $this->serializer->log(Level::INFO, $metadata);
        $this->validate(ob_get_clean())
            ->as("\n\n\033[1;4mmetadata info\033[0m:" .
                "\n\033[4min\033[0m: \033[0;4m#0" .
                "\n\033[0mat: #1" .
                "\nas: #2" .
                "\n\033[4min\033[0m: \033[0;4m#3" .
                "\033[0m\nat: 33 - #4".
                "\nis: ");
    }

    public function testRequest(): void
    {
        $request = $this->createStub(Request::class);

        $request->fake("getPath")
            ->return([["layer" => "#0", "breadcrumb" => ["#1"], "source" => "#2"]])
            ->fake("getSources")
            ->return(["#3"]);

        ob_start();
        $this->serializer->log(Level::INFO, $request);
        $this->validate(ob_get_clean())
            ->as("\n\n\033[1;4mrequest info\033[0m:" .
                "\n\033[4min\033[0m: \033[0;4m#0" .
                "\n\033[0mat: #1" .
                "\nas: #2" .
                "\n\033[4mby\033[0m: \033[0;4m#3" .
                "\n\033[0mis: ");
    }

    public function testErrorInfo(): void
    {
        $error = $this->createStub(InfoError::class);

        $error->fake("getPath")
            ->return([["line" => "#0", "file" => "#1", "class" => "#2",
                "type" => "#3", "function" => "#4"]])
            ->fake("getCode")
            ->return(11)
            ->fake("getMessage")
            ->return("#5");

        ob_start();
        $this->serializer->log(Level::INFO, $error);
        $this->validate(ob_get_clean())
            ->as("\n\n\033[4min\033[0m: \033[0;4m#0 - #1" .
                "\n\033[0mat: #2#3#4()" .
                "\nis: #5 | code: 11");
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
            ->return("#8")
            ->fake("getType")
            ->return("#9");

        ob_start();
        $this->serializer->log(Level::INFO, $content);
        $this->validate(ob_get_clean())
            ->as("\n\n\033[4m#3\033[0m | #4".
                "\nname: #5".
                "\ndescription: #6".
                "\ntype: #9 | ".
                "\nsource: #7".
                "\ndir: #8");
    }

    public function testLifecycle(): void
    {
        $lifecycle = $this->createStub(Lifecycle::class);

        $lifecycle->fake("getPath")
            ->return([["layer" => "#0", "breadcrumb" => ["#1"], "source" => "#2"]])
            ->fake("getLayer")
            ->return("#3")
            ->fake("getBreadcrumb")
            ->return(["#4"]);

        ob_start();
        $this->serializer->log(Level::INFO, $lifecycle);
        $this->validate(ob_get_clean())
            ->as("\n\n\033[1;4mlifecycle info\033[0m:" .
                "\n\033[4min\033[0m: \033[0;4m#0" .
                "\n\033[0mat: #1" .
                "\nas: #2" .
                "\n\033[4min\033[0m: \033[0;4m#3" .
                "\n\033[0mat: #4" .
                "\nis: ");
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
            ->as("\n\n\033[1;4mconfig info\033[0m:" .
                "\n\033[4min\033[0m: \033[0;4m#3" .
                "\n\033[0mat: #4" .
                "\nis: ");
    }

    public function testId(): void
    {
        $id = $this->createStub(Id::class);

        $id->fake("getId")
            ->return("#3");

        ob_start();
        $this->serializer->log(Level::INFO, $id);
        $this->validate(ob_get_clean())
            ->as("\nexecute \033[4m#3\033[0m id");
    }

    public function testName(): void
    {
        $name = $this->createStub(Name::class);

        $name->fake("getName")
            ->return("#3");

        ob_start();
        $this->serializer->log(Level::INFO, $name);
        $this->validate(ob_get_clean())
            ->as("\n\n\033[4;32m#3\033[0m");
    }
}