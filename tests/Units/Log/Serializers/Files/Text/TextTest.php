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

namespace Valvoid\Fusion\Tests\Units\Log\Serializers\Files\Text;

use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Log\Events\Errors\Config;
use Valvoid\Fusion\Log\Events\Errors\Deadlock;
use Valvoid\Fusion\Log\Events\Errors\Environment;
use Valvoid\Fusion\Log\Events\Errors\Lifecycle;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Events\Infos\Error;
use Valvoid\Fusion\Log\Events\Errors\Metadata;
use Valvoid\Fusion\Log\Events\Errors\Request;
use Valvoid\Fusion\Log\Events\Infos\Id;
use Valvoid\Fusion\Log\Events\Infos\Name;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Log\Serializers\Files\Text\Text;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Reflex\Test\Wrapper;

class TextTest extends Wrapper
{
    public function testMessage(): void
    {
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);

        $dir->fake("getLogDir")
            ->return("#0")
            ->fake("createDir")
            ->expect(dir: "#0")
            ->return(true);

        $file->fake("put")
            ->hook(function ($file, $data) {
                $this->validate($file)
                    ->as("#0/#1");

                $data = substr($data, 20);

                $this->validate($data)
                    ->as(" --------- generic info:\n###");

                return true;
            });

        $serializer = new Text(
            directory: $dir,
            file: $file,
            configuration: [
                "threshold" => Level::INFO,
                "filename" => "#1",
            ]
        );

        $serializer->log(Level::INFO, "###");
    }

    public function testDeadlock(): void
    {
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $deadlock = $this->createMock(Deadlock::class);

        $dir->fake("getLogDir")
            ->return("#0")
            ->fake("createDir")
            ->expect(dir: "#0")
            ->return(true);

        $file->fake("put")
            ->hook(function ($file, $data) {
                $this->validate($file)
                    ->as("#0/#1");

                $data = substr($data, 20);

                $this->validate($data)
                    ->as(" --------- deadlock info:" .
                        "\nin: #3" .
                        "\nat: #4" .
                        "\nas: #5" .
                        "\nin: #9" .
                        "\nat: #11" .
                        "\n    ---" .
                        "\nin: #6" .
                        "\nat: #7" .
                        "\nas: #8" .
                        "\nin: #10" .
                        "\nat: #12" .
                        "\nis: ");

                return true;
            });

        $deadlock->fake("getLockedPath")
            ->return([["layer" => "#3", "breadcrumb" => ["#4"], "source" => "#5"]])
            ->fake("getConflictPath")
            ->return([["layer" => "#6", "breadcrumb" => ["#7"], "source" => "#8"]])
            ->fake("getLockedLayer")
            ->return("#9")
            ->fake("getConflictLayer")
            ->return("#10")
            ->fake("getLockedBreadcrumb")
            ->return(["#11"])
            ->fake("getConflictBreadcrumb")
            ->return(["#12"]);

        $serializer = new Text(
            directory: $dir,
            file: $file,
            configuration: [
                "threshold" => Level::INFO,
                "filename" => "#1",
            ]
        );

        $serializer->log(Level::INFO, $deadlock);
    }

    public function testEnvironment(): void
    {
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $environment = $this->createMock(Environment::class);

        $dir->fake("getLogDir")
            ->return("#0")
            ->fake("createDir")
            ->expect(dir: "#0")
            ->return(true);

        $file->fake("put")
            ->hook(function ($file, $data) {
                $this->validate($file)
                    ->as("#0/#1");

                $data = substr($data, 20);

                $this->validate($data)
                    ->as(" --------- environment info:" .
                        "\nin: #3" .
                        "\nat: #4" .
                        "\nas: #5" .
                        "\nin: #6" .
                        "\nat: #7" .
                        "\nis: ");

                return true;
            });

        $environment->fake("getPath")
            ->return([["layer" => "#3", "breadcrumb" => ["#4"], "source" => "#5"]])
            ->fake("getLayer")
            ->return("#6")
            ->fake("getBreadcrumb")
            ->return(["#7"]);

        $serializer = new Text(
            directory: $dir,
            file: $file,
            configuration: [
                "threshold" => Level::INFO,
                "filename" => "#1",
            ]
        );

        $serializer->log(Level::INFO, $environment);
    }

    public function testMetadata(): void
    {
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $metadata = $this->createMock(Metadata::class);

        $dir->fake("getLogDir")
            ->return("#0")
            ->fake("createDir")
            ->expect(dir: "#0")
            ->return(true);

        $file->fake("put")
            ->hook(function ($file, $data) {
                $this->validate($file)
                    ->as("#0/#1");

                $data = substr($data, 20);

                $this->validate($data)
                    ->as(" --------- metadata info:" .
                        "\nin: #3" .
                        "\nat: #4" .
                        "\nas: #5" .
                        "\nin: #6" .
                        "\nat: #7" .
                        "\nis: ");

                return true;
            });

        $metadata->fake("getPath")
            ->return([["layer" => "#3", "breadcrumb" => ["#4"], "source" => "#5"]])
            ->fake("getLayer")
            ->return("#6")
            ->fake("getBreadcrumb")
            ->return(["#7"]);

        $serializer = new Text(
            directory: $dir,
            file: $file,
            configuration: [
                "threshold" => Level::INFO,
                "filename" => "#1",
            ]
        );

        $serializer->log(Level::INFO, $metadata);
    }

    public function testRequest(): void
    {
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $request = $this->createMock(Request::class);

        $dir->fake("getLogDir")
            ->return("#0")
            ->fake("createDir")
            ->expect(dir: "#0")
            ->return(true);

        $file->fake("put")
            ->hook(function ($file, $data) {
                $this->validate($file)
                    ->as("#0/#1");

                $data = substr($data, 20);

                $this->validate($data)
                    ->as(" --------- request info:" .
                        "\nin: #3" .
                        "\nat: #4" .
                        "\nas: #5" .
                        "\nby: #6" .
                        "\nis: ");

                return true;
            });

        $request->fake("getPath")
            ->return([["layer" => "#3", "breadcrumb" => ["#4"], "source" => "#5"]])
            ->fake("getSources")
            ->return(["#6"]);

        $serializer = new Text(
            directory: $dir,
            file: $file,
            configuration: [
                "threshold" => Level::INFO,
                "filename" => "#1",
            ]
        );

        $serializer->log(Level::INFO, $request);
    }

    public function testErrorInfo(): void
    {
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $error = $this->createMock(Error::class);

        $dir->fake("getLogDir")
            ->return("#0")
            ->fake("createDir")
            ->expect(dir: "#0")
            ->return(true);

        $file->fake("put")
            ->hook(function ($file, $data) {
                $this->validate($file)
                    ->as("#0/#1");

                $data = substr($data, 20);

                $this->validate($data)
                    ->as(" --------- error info info:" .
                        "\nin: #3 - #4" .
                        "\nat: #5()" .
                        "\nis: #6 | code: 11");

                return true;
            });

        $error->fake("getPath")
            ->return([["line" => "#3", "file" => "#4", "function" => "#5"]])
            ->fake("getCode")
            ->return(11)
            ->fake("getMessage")
            ->return("#6");

        $serializer = new Text(
            directory: $dir,
            file: $file,
            configuration: [
                "threshold" => Level::INFO,
                "filename" => "#1",
            ]
        );

        $serializer->log(Level::INFO, $error);
    }

    public function testContent(): void
    {
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $content = $this->createMock(Content::class);

        $dir->fake("getLogDir")
            ->return("#0")
            ->fake("createDir")
            ->expect(dir: "#0")
            ->return(true);

        $file->fake("put")
            ->hook(function ($file, $data) {
                $this->validate($file)
                    ->as("#0/#1");

                $data = substr($data, 20);

                $this->validate($data)
                    ->as(" --------- content info:" .
                        "\n#3 | #4" .
                        "\nname: #5" .
                        "\ndescription: #6" .
                        "\nsource: #7" .
                        "\ndir: #8");

                return true;
            });

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

        $serializer = new Text(
            directory: $dir,
            file: $file,
            configuration: [
                "threshold" => Level::VERBOSE,
                "filename" => "#1",
            ]
        );

        $serializer->log(Level::INFO, $content);
    }

    public function testLifecycle(): void
    {
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $lifecycle = $this->createMock(Lifecycle::class);

        $dir->fake("getLogDir")
            ->return("#0")
            ->fake("createDir")
            ->expect(dir: "#0")
            ->return(true);

        $file->fake("put")
            ->hook(function ($file, $data) {
                $this->validate($file)
                    ->as("#0/#1");

                $data = substr($data, 20);

                $this->validate($data)
                    ->as(" --------- lifecycle info:" .
                        "\nin: #3" .
                        "\nat: #4" .
                        "\nas: #5" .
                        "\nin: #6" .
                        "\nat: #7" .
                        "\nis: ");

                return true;
            });

        $lifecycle->fake("getPath")
            ->return([["layer" => "#3", "breadcrumb" => ["#4"], "source" => "#5"]])
            ->fake("getLayer")
            ->return("#6")
            ->fake("getBreadcrumb")
            ->return(["#7"]);

        $serializer = new Text(
            directory: $dir,
            file: $file,
            configuration: [
                "threshold" => Level::INFO,
                "filename" => "#1",
            ]
        );

        $serializer->log(Level::INFO, $lifecycle);
    }

    public function testConfig(): void
    {
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $config = $this->createMock(Config::class);

        $dir->fake("getLogDir")
            ->return("#0")
            ->fake("createDir")
            ->expect(dir: "#0")
            ->return(true);

        $file->fake("put")
            ->hook(function ($file, $data) {
                $this->validate($file)
                    ->as("#0/#1");

                $data = substr($data, 20);

                $this->validate($data)
                    ->as(" --------- config info:" .
                        "\nin: #3" .
                        "\nat: #4" .
                        "\nis: ");

                return true;
            });

        $config->fake("getLayer")
            ->return("#3")
            ->fake("getBreadcrumb")
            ->return(["#4"]);

        $serializer = new Text(
            directory: $dir,
            file: $file,
            configuration: [
                "threshold" => Level::INFO,
                "filename" => "#1",
            ]
        );

        $serializer->log(Level::INFO, $config);
    }

    public function testId(): void
    {
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $id = $this->createMock(Id::class);

        $dir->fake("getLogDir")
            ->return("#0")
            ->fake("createDir")
            ->expect(dir: "#0")
            ->return(true);

        $file->fake("put")
            ->hook(function ($file, $data) {
                $this->validate($file)
                    ->as("#0/#1");

                $data = substr($data, 20);

                $this->validate($data)
                    ->as(" --------- id info:" .
                        "\nid: #3");

                return true;
            });

        $id->fake("getId")
            ->return("#3");

        $serializer = new Text(
            directory: $dir,
            file: $file,
            configuration: [
                "threshold" => Level::INFO,
                "filename" => "#1",
            ]
        );

        $serializer->log(Level::INFO, $id);
    }

    public function testName(): void
    {
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $name = $this->createMock(Name::class);

        $dir->fake("getLogDir")
            ->return("#0")
            ->fake("createDir")
            ->expect(dir: "#0")
            ->return(true);

        $file->fake("put")
            ->hook(function ($file, $data) {
                $this->validate($file)
                    ->as("#0/#1");

                $data = substr($data, 20);

                $this->validate($data)
                    ->as(" --------- name info:" .
                        "\nname: #3");

                return true;
            });

        $name->fake("getName")
            ->return("#3");

        $serializer = new Text(
            directory: $dir,
            file: $file,
            configuration: [
                "threshold" => Level::INFO,
                "filename" => "#1",
            ]
        );

        $serializer->log(Level::INFO, $name);
    }
}