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

namespace Valvoid\Fusion\Tests\Units\Dir;

use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Cache;
use Valvoid\Fusion\Config\Config;
use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Reflex\Test\Wrapper;
use Valvoid\Fusion\Wrappers\Dir as DirWrapper;

class DirTest extends Wrapper
{
    public function testReplaceContent(): void
    {
        $dirWrapper = $this->createMock(DirWrapper::class);
        $file = $this->createMock(File::class);
        $bus = $this->createMock(Bus::class);
        $config = $this->createMock(Config::class);

        $config->fake("get")
            ->expect(breadcrumb: ["state", "path"])
            ->return("#s")
            ->expect(breadcrumb: ["cache", "path"])
            ->return("#c")
            ->expect(breadcrumb: ["dir", "path"])
            ->return("#")
            ->expect(breadcrumb: ["dir", "clearable"])
            ->return(true);

        $bus->fake("addReceiver")
            ->expect(id: Dir::class, events: [Cache::class]);

        $dirWrapper->fake("is")
            ->expect(dir: "#")
            ->return(true)
            ->expect(dir: "#/f0")
            ->return(false)
            ->fake("getFilenames")
            ->expect(dir: "#")
            ->return(["f0"]);

        $file->fake("is")
            ->expect(file: "#/f0")
            ->return(true)
            ->fake("unlink")
            ->expect(file: "#/f0")
            ->return(true)
            ->fake("copy")
            ->expect(from: dirname(__DIR__, 3) .
                "/src/Dir/placeholder.json", to: "#/fusion.json")
            ->return(true);

        $dir = new Dir(
            dir: $dirWrapper,
            file: $file,
            bus: $bus,
            config: $config
        );

        $this->validate($dir->getStatefulDir())
            ->as("#/state");

        $this->validate($dir->getHubDir())
            ->as("#c/hub");

        $this->validate($dir->getLogDir())
            ->as("#s/log");

        $this->validate($dir->getOtherDir())
            ->as("#s/other");

        $this->validate($dir->getPackagesDir())
            ->as("#s/packages");

        $this->validate($dir->getRootDir())
            ->as("#");

        $this->validate($dir->getStateDir())
            ->as("#s/state");

        $this->validate($dir->getTaskDir())
            ->as("#s/task");
    }

    public function testRecycleContent(): void
    {
        $dirWrapper = $this->createMock(DirWrapper::class);
        $file = $this->createMock(File::class);
        $bus = $this->recycleMock(Bus::class);
        $config = $this->createMock(Config::class);

        $config->fake("get")
            ->expect(breadcrumb: ["state", "path"])
            ->return("#s")
            ->expect(breadcrumb: ["cache", "path"])
            ->return("#c")
            ->expect(breadcrumb: ["dir", "path"])
            ->return("#")
            ->expect(breadcrumb: ["dir", "clearable"])
            ->return(false);

        $dirWrapper->fake("is")
            ->expect(dir: "#")
            ->return(true);

        $file->fake("exists")
            ->expect(file: "#/fusion.json")
            ->return(true)
            ->fake("get")
            ->expect(file: "#/fusion.json")
            ->return('{"structure": {"/##": "stateful"}}');

        $dir = new Dir(
            dir: $dirWrapper,
            file: $file,
            bus: $bus,
            config: $config
        );

        $this->validate($dir->getStatefulDir())
            ->as("#/##");

        $this->validate($dir->getHubDir())
            ->as("#c/hub");

        $this->validate($dir->getLogDir())
            ->as("#s/log");

        $this->validate($dir->getOtherDir())
            ->as("#s/other");

        $this->validate($dir->getPackagesDir())
            ->as("#s/packages");

        $this->validate($dir->getRootDir())
            ->as("#");

        $this->validate($dir->getStateDir())
            ->as("#s/state");

        $this->validate($dir->getTaskDir())
            ->as("#s/task");
    }

    public function testRecycleEmptyContent(): void
    {
        $dirWrapper = $this->createMock(DirWrapper::class);
        $file = $this->createMock(File::class);
        $bus = $this->recycleMock(Bus::class);
        $config = $this->createMock(Config::class);

        $config->fake("get")
            ->expect(breadcrumb: ["state", "path"])
            ->return("#s")
            ->expect(breadcrumb: ["cache", "path"])
            ->return("#c")
            ->expect(breadcrumb: ["dir", "path"])
            ->return("#")
            ->expect(breadcrumb: ["dir", "clearable"])
            ->return(false);

        $dirWrapper->fake("is")
            ->expect(dir: "#")
            ->return(true)
            ->fake("getFilenames")
            ->return([]);

        $file->fake("exists")
            ->expect(file: "#/fusion.json")
            ->return(false)
            ->fake("copy")
            ->expect(from: dirname(__DIR__, 3) .
                "/src/Dir/placeholder.json", to: "#/fusion.json")
            ->return(true);

        $dir = new Dir(
            dir: $dirWrapper,
            file: $file,
            bus: $bus,
            config: $config
        );

        $this->validate($dir->getStatefulDir())
            ->as("#/state");

        $this->validate($dir->getHubDir())
            ->as("#c/hub");

        $this->validate($dir->getLogDir())
            ->as("#s/log");

        $this->validate($dir->getOtherDir())
            ->as("#s/other");

        $this->validate($dir->getPackagesDir())
            ->as("#s/packages");

        $this->validate($dir->getRootDir())
            ->as("#");

        $this->validate($dir->getStateDir())
            ->as("#s/state");

        $this->validate($dir->getTaskDir())
            ->as("#s/task");
    }

    public function testCreateContent(): void
    {
        $dirWrapper = $this->createMock(DirWrapper::class);
        $file = $this->createMock(File::class);
        $bus = $this->recycleMock(Bus::class);
        $config = $this->createMock(Config::class);

        $config->fake("get")
            ->expect(breadcrumb: ["state", "path"])
            ->return("#s")
            ->expect(breadcrumb: ["cache", "path"])
            ->return("#c")
            ->expect(breadcrumb: ["dir", "path"])
            ->return("#")
            ->expect(breadcrumb: ["dir", "creatable"])
            ->return(true);

        $dirWrapper->fake("is")
            ->expect(dir: "#")
            ->return(false)
            ->fake("create")
            ->expect(dir: "#")
            ->return(true);

        $file->fake("copy")
            ->expect(from: dirname(__DIR__, 3) .
                "/src/Dir/placeholder.json", to: "#/fusion.json")
            ->return(true);

        $dir = new Dir(
            dir: $dirWrapper,
            file: $file,
            bus: $bus,
            config: $config
        );

        $this->validate($dir->getStatefulDir())
            ->as("#/state");

        $this->validate($dir->getHubDir())
            ->as("#c/hub");

        $this->validate($dir->getLogDir())
            ->as("#s/log");

        $this->validate($dir->getOtherDir())
            ->as("#s/other");

        $this->validate($dir->getPackagesDir())
            ->as("#s/packages");

        $this->validate($dir->getRootDir())
            ->as("#");

        $this->validate($dir->getStateDir())
            ->as("#s/state");

        $this->validate($dir->getTaskDir())
            ->as("#s/task");
    }

    public function testNewStatefulDir(): void
    {
        $dirWrapper = $this->recycleMock(DirWrapper::class);
        $file = $this->recycleMock(File::class);
        $bus = $this->createMock(Bus::class);
        $config = $this->recycleMock(Config::class);
        $event = $this->createStub(Cache::class);
        $closure = null;

        $bus->fake("addReceiver")
            ->hook(function ($id, $callback, $events) use (&$closure) {
                $closure = $callback;

                $this->validate($id)
                    ->as(Dir::class);

                $this->validate($events)
                    ->as([Cache::class]);
            });

        $dir = new Dir(
            dir: $dirWrapper,
            file: $file,
            bus: $bus,
            config: $config
        );

        $this->validate($dir->getStatefulDir())
            ->as("#/state");

        $event->fake("getDir")
            ->return("####");

        call_user_func($closure, $event);

        $this->validate($dir->getStatefulDir())
            ->as("####");
    }

    public function testClear(): void
    {
        $dirWrapper = $this->createMock(DirWrapper::class);
        $file = $this->recycleMock(File::class);
        $bus = $this->recycleMock(Bus::class);
        $config = $this->recycleMock(Config::class);

        $dirWrapper->fake("is")
            ->expect(dir: "#")
            ->return(false)
            ->expect(dir: "#0/#1/#2")
            ->return(true)
            ->expect(dir: "#0/#1")
            ->fake("create")
            ->expect(dir: "#")
            ->return(true)
            ->fake("getFilenames")
            ->expect(dir: "#0/#1/#2")
            ->return([])
            ->expect(dir: "#0/#1")
            ->return([2 => "f0"]) // ".", "..",
            ->fake("delete")
            ->expect(dir: "#0/#1/#2")
            ->return(true);

        $dir = new Dir(
            dir: $dirWrapper,
            file: $file,
            bus: $bus,
            config: $config
        );

        $dir->clear("#0", "/#1/#2");
    }

    public function testCreateDir(): void
    {
        $dirWrapper = $this->createMock(DirWrapper::class);
        $file = $this->createMock(File::class);
        $bus = $this->recycleMock(Bus::class);
        $config = $this->recycleMock(Config::class);

        $dirWrapper->fake("is")
            ->expect(dir: "#")
            ->return(false)
            ->fake("create")
            ->expect(dir: "#")
            ->return(true)
            ->expect(dir: "###", permissions: 1111);

        $file->fake("copy")
            ->expect(from: dirname(__DIR__, 3) .
                "/src/Dir/placeholder.json", to: "#/fusion.json")
            ->return(true)
            ->fake("exists")
            ->return(false);

        $dir = new Dir(
            dir: $dirWrapper,
            file: $file,
            bus: $bus,
            config: $config
        );

        $dir->createDir("###", 1111);
    }

    public function testRenameFile(): void
    {
        $dirWrapper = $this->createMock(DirWrapper::class);
        $file = $this->createMock(File::class);
        $bus = $this->recycleMock(Bus::class);
        $config = $this->recycleMock(Config::class);

        $dirWrapper->fake("is")
            ->expect(dir: "#")
            ->return(false)
            ->fake("create")
            ->expect(dir: "#")
            ->return(true)
            ->fake("rename")
            ->expect(from: "#0", to: "#1")
            ->return(true);

        $file->fake("copy")
            ->expect(from: dirname(__DIR__, 3) .
                "/src/Dir/placeholder.json", to: "#/fusion.json")
            ->return(true)
            ->fake("is")
            ->expect(file: "#1")
            ->return(true)
            ->fake("unlink")
            ->expect(file: "#1")
            ->return(true);

        $dir = new Dir(
            dir: $dirWrapper,
            file: $file,
            bus: $bus,
            config: $config
        );

        $dir->rename("#0", "#1");
    }

    public function testRenameDir(): void
    {
        $dirWrapper = $this->createMock(DirWrapper::class);
        $file = $this->createMock(File::class);
        $bus = $this->recycleMock(Bus::class);
        $config = $this->recycleMock(Config::class);

        $dirWrapper->fake("is")
            ->expect(dir: "#")
            ->return(false)
            ->expect(dir: "#1")
            ->return(true)
            ->fake("create")
            ->expect(dir: "#")
            ->return(true)
            ->fake("delete")
            ->return(true)
            ->fake("rename")
            ->expect(from: "#0", to: "#1")
            ->return(true);

        $file->fake("copy")
            ->expect(from: dirname(__DIR__, 3) .
                "/src/Dir/placeholder.json", to: "#/fusion.json")
            ->return(true)
            ->fake("is")
            ->expect(file: "#1")
            ->return(false);

        $dir = new Dir(
            dir: $dirWrapper,
            file: $file,
            bus: $bus,
            config: $config
        );

        $dir->rename("#0", "#1");
    }

    public function testDelete(): void
    {
        $dirWrapper = $this->createMock(DirWrapper::class);
        $file = $this->createMock(File::class);
        $bus = $this->recycleMock(Bus::class);
        $config = $this->recycleMock(Config::class);

        $dirWrapper->fake("is")
            ->expect(dir: "#")
            ->return(false)
            ->expect(dir: "#0")
            ->return(true)
            ->expect(dir: "#0/#1")
            ->expect(dir: "#0/#2")
            ->return(false)
            ->fake("create")
            ->expect(dir: "#")
            ->return(true)
            ->fake("getFilenames")
            ->expect(dir: "#0")
            ->return(["#1", "#2"])
            ->expect(dir: "#0/#1")
            ->return([])
            ->fake("delete")
            ->expect(dir: "#0/#1")
            ->return(true)
            ->expect(dir: "#0");

        $file->fake("copy")
            ->expect(from: dirname(__DIR__, 3) .
                "/src/Dir/placeholder.json", to: "#/fusion.json")
            ->return(true)
            ->fake("is")
            ->expect(file: "#0/#2")
            ->return(true)
            ->fake("unlink")
            ->expect(file: "#0/#2")
            ->return(true);

        $dir = new Dir(
            dir: $dirWrapper,
            file: $file,
            bus: $bus,
            config: $config
        );

        $dir->delete("#0");
    }
}