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

namespace Valvoid\Fusion\Tests\Units;

use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Root;
use Valvoid\Fusion\Config\Config;
use Valvoid\Fusion\Dir\Dir as Directory;
use Valvoid\Fusion\Fusion;
use Valvoid\Fusion\Hub\Hub;
use Valvoid\Fusion\Log\Events\Infos\Name;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tasks\Task;
use Valvoid\Fusion\Wrappers\Dir;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Reflex\Test\Wrapper;

class FusionTest extends Wrapper
{
    public function testTask(): void
    {
        $box = $this->createMock(Box::class);
        $file = $this->createMock(File::class);
        $dir = $this->createMock(Dir::class);
        $directory = $this->createMock(Directory::class);
        $task = $this->createMock(Task::class);
        $bus = $this->createMock(Bus::class);
        $config = $this->createMock(Config::class);
        $log = $this->createMock(Log::class);
        $entry = ["task" => $task::class, "##" => "##"];

        $dir->fake("getDirname")
            ->return("#");

        $box->fake("get")
            ->expect(class: Bus::class)
            ->return($bus)
            ->expect(class: Config::class,
                arguments: ["root" => "#", // fusion
                    "path" =>  "/#", // project root
                    "prefixes" => [],
                    "config" => []])
            ->return($config)
            ->expect(class: Log::class)
            ->return($log)
            ->expect(class: Config::class)
            ->return($config)
            ->expect(class: Directory::class)
            ->return($directory)
            ->expect(class: $task::class,
                arguments: ["config" => $entry])
            ->return($task)
            ->fake("recycle")
            ->expect(classes: [Bus::class,
                Log::class,
                Config::class,
                Group::class,
                Directory::class,
                Hub::class]);

        $config->fake("load")
            ->expect(overlay: true)
            ->return(true)
            ->fake("get")
            ->return($entry);

        $bus->fake("addReceiver")
            ->expect(id: Fusion::class,
                events: [Root::class]);

        $directory->fake("getStateDir")
            ->return("#s")
            ->fake("getTaskDir")
            ->return("#t")
            ->fake("getPackagesDir")
            ->return("#p")
            ->fake("getOtherDir")
            ->return("#o")
            ->fake("delete")
            ->expect(file: "#s")
            ->expect(file: "#t")
            ->expect(file: "#p")
            ->expect(file: "#o");

        $task->fake("execute")
            ->return(null);

        $fusion = new Fusion(
            box: $box,
            prefixes: [],
            root: "/#",
            file: $file,
            dir: $dir,
            config: []);

        $fusion->execute("test");
    }

    public function testTaskGroup(): void
    {
        $box = $this->createMock(Box::class);
        $file = $this->createMock(File::class);
        $dir = $this->recycleMock(Dir::class);
        $directory = $this->recycleMock(Directory::class);
        $task = $this->recycleMock(Task::class);
        $bus = $this->recycleMock(Bus::class);
        $config = $this->createMock(Config::class);
        $log = $this->createMock(Log::class);
        $name = $this->createStub(Name::class);
        $entry = ["task" => $task::class, "##" => "##"];

        $box->fake("get")
            ->expect(class: Bus::class)
            ->return($bus)
            ->expect(class: Config::class,
                arguments: ["root" => "#", // fusion
                    "path" =>  "/#", // project root
                    "prefixes" => [],
                    "config" => []])
            ->return($config)
            ->expect(class: Log::class)
            ->return($log)
            ->expect(class: Config::class)
            ->return($config)
            ->expect(class: Directory::class) // normalize
            ->return($directory)
            ->expect(class: Name::class,
                arguments: ["name" => "group"])
            ->return($name)
            ->expect(class: $task::class,
                arguments: ["config" => $entry])
            ->return($task)
            ->fake("recycle")
            ->expect(classes: [Bus::class,
                Log::class,
                Config::class,
                Group::class,
                Directory::class,
                Hub::class]);

        $log->fake("info")
            ->expect(event: $name);

        $config->fake("load")
            ->expect(overlay: true)
            ->return(true)
            ->fake("get")
            ->return(["group" => $entry]);

        $fusion = new Fusion(
            box: $box,
            prefixes: [],
            root: "/#",
            file: $file,
            dir: $dir,
            config: []);

        $fusion->execute("test");
    }
}