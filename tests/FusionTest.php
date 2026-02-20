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

namespace Valvoid\Fusion\Tests;

use Throwable;
use Valvoid\Fusion\Fusion;
use Valvoid\Fusion\Log\Events\Infos\Name;
use Valvoid\Fusion\Tests\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Mocks\BusMock;
use Valvoid\Fusion\Tests\Mocks\ConfigMock;
use Valvoid\Fusion\Tests\Mocks\DirectoryMock;
use Valvoid\Fusion\Tests\Mocks\DirMock;
use Valvoid\Fusion\Tests\Mocks\FileMock;
use Valvoid\Fusion\Tests\Mocks\LogMock;
use Valvoid\Fusion\Tests\Mocks\TaskMock;

class FusionTest extends Test
{
    protected string|array $coverage = Fusion::class;
    private BoxMock $box;

    public function __construct()
    {
        $this->box = new BoxMock;

        $this->testNoState();
        $this->testState();
        $this->testTaskGroup();
        $this->testTask();

        $this->box::unsetInstance();
    }

    public function testNoState(): void
    {
        try {
            $is =
            $load = [];
            $file = new FileMock;
            $file->is = function ($file) use (&$is) {
                if ($file == "/#/state/prefixes.php" ||
                    $file == "/#/fusion.json")
                    $is[] = $file;

                return $file == "/#/fusion.json";
            };
            $dir = new DirMock;
            $dir->dirname = fn () => "/#";

            $bus = new BusMock;
            $bus->add = function ($id, $callback, ...$events) {};
            $config = new ConfigMock;
            $config->load = function ($overlay) use (&$load) {
                $load[] = $overlay;
            };
            $config->get = function (string ...$breadcrumb) {return [];};
            $this->box->get = function ($class, ...$args) use ($bus, $config) {
                if ($class == "Valvoid\Fusion\Bus\Bus")
                    return $bus;
                if ($class == "Valvoid\Fusion\Config\Config")
                    return $config;
            };

            new Fusion(
                box: $this->box,
                file: $file,
                dir: $dir,
                config: []);

            if ($is != [
                    "/#/fusion.json",
                    "/#/state/prefixes.php"] ||
                $load != [true])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testState(): void
    {
        try {
            $is =
            $load =
            $require = [];
            $file = new FileMock;
            $file->is = function ($file) use (&$is) {
                if ($file == "/#/state/prefixes.php" ||
                    $file == "/#/fusion.json") {
                    $is[] = $file;

                    return true;
                }

                // autloader
                return false;
            };
            $file->require = function ($file) use (&$require) {
                $require[] = $file;

                return ["###" => ""];
            };

            $dir = new DirMock;
            $dir->dirname = fn () => "/#";
            $bus = new BusMock;
            $bus->add = function ($id, $callback, ...$events) {};
            $config = new ConfigMock;
            $config->load = function ($overlay) use (&$load) {
                $load[] = $overlay;
            };
            $config->get = function (string ...$breadcrumb) {return [];};
            $this->box->get = function ($class, ...$args) use ($bus, $config) {
                if ($class == "Valvoid\Fusion\Bus\Bus")
                    return $bus;
                if ($class == "Valvoid\Fusion\Config\Config")
                    return $config;
            };

            new Fusion(
                box: $this->box,
                file: $file,
                dir: $dir,
                config: []);

            if ($is != [
                    "/#/fusion.json",
                    "/#/state/prefixes.php"] ||
                $require != ["/#/state/prefixes.php"] ||
                $load != [true])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testTaskGroup(): void
    {
        try {
            $is =
            $load =
            $cGet =
            $tasks =[];
            $file = new FileMock;
            $file->is = function ($file) use (&$is) {
                if ($file == "/#/state/prefixes.php" ||
                    $file == "/#/fusion.json")
                    $is[] = $file;

                return $file == "/#/fusion.json";
            };
            $dir = new DirMock;
            $dir->dirname = fn () => "/#";
            $bus = new BusMock;
            $bus->add = function ($id, $callback, ...$events) {};
            $config = new ConfigMock;
            $config->load = function ($overlay) use (&$load) {
                $load[] = $overlay;
            };
            $config->get = function (string ...$breadcrumb) use (&$cGet) {
                $cGet[] = $breadcrumb;

                return [
                    "id0" => [
                        "task" => "task0",
                    ],"id1" => [
                        "task" => "task1",
                    ]];
            };
            $directory = new DirectoryMock;
            $directory->delete = function ($file) use (&$delete) {
                $delete[] = $file;
            };
            $directory->state = fn () => "/#s";
            $directory->task = fn () => "/#t";
            $directory->packages = fn () => "/#p";
            $directory->other = fn () => "/#o";

            $this->box->get = function ($class, ...$args) use
            ($bus, $config, $directory, &$tasks) {
                if ($class == "Valvoid\Fusion\Bus\Bus")
                    return $bus;
                if ($class == "Valvoid\Fusion\Config\Config")
                    return $config;

                if ($class == "Valvoid\Fusion\Log\Log")
                    return new LogMock;

                if ($class == "Valvoid\Fusion\Dir\Dir")
                    return $directory;

                if ($class == "Valvoid\Fusion\Log\Events\Infos\Name")
                    return new Name("");

                if ($class == "Valvoid\Fusion\Log\Events\Infos\Name")
                    return new Name("");

                if ($class == "task0" || $class == "task1") {
                    $mock = new TaskMock(...$args);
                    $tasks[$class] = $mock;
                    return $mock;
                }
            };

            $fusion = new Fusion(
                box: $this->box,
                file: $file,
                dir: $dir,
                config: []);

            $fusion->execute("test");

            foreach ($tasks as $class => $task)
                if (($class != "task0" && $class != "task1") ||
                    (!$task instanceof TaskMock) ||
                    $task->executes != 1 ||
                    $task->config != ["task" => $class]) {
                    $this->handleFailedTest();
                    break;
                }

            if ($is != [
                    "/#/fusion.json",
                    "/#/state/prefixes.php"] ||
                sizeof($tasks) != 2 ||
                $cGet != [["tasks", "test"]] || // group
                $delete != ["/#s", "/#t", "/#p", "/#o"] ||
                $load != [true])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testTask(): void
    {
        try {
            $is =
            $load =
            $cGet =
            $tasks = [];
            $file = new FileMock;
            $file->is = function ($file) use (&$is) {
                if ($file == "/#/state/prefixes.php" ||
                    $file == "/#/fusion.json")
                    $is[] = $file;

                return $file == "/#/fusion.json";
            };
            $dir = new DirMock;
            $dir->dirname = fn () => "/#";

            $bus = new BusMock;
            $bus->add = function ($id, $callback, ...$events) {};
            $config = new ConfigMock;
            $config->load = function ($overlay) use (&$load) {
                $load[] = $overlay;
            };
            $config->get = function (string ...$breadcrumb) use (&$cGet) {
                $cGet[] = $breadcrumb;

                return [
                    "task" => "task0",
                ];
            };
            $directory = new DirectoryMock;
            $directory->delete = function ($file) use (&$delete) {
                $delete[] = $file;
            };
            $directory->state = fn () => "/#s";
            $directory->task = fn () => "/#t";
            $directory->packages = fn () => "/#p";
            $directory->other = fn () => "/#o";

            $this->box->get = function ($class, ...$args) use
            ($bus, $config, $directory, &$tasks) {
                if ($class == "Valvoid\Fusion\Bus\Bus")
                    return $bus;
                if ($class == "Valvoid\Fusion\Config\Config")
                    return $config;

                if ($class == "Valvoid\Fusion\Log\Log")
                    return new LogMock;

                if ($class == "Valvoid\Fusion\Dir\Dir")
                    return $directory;

                if ($class == "Valvoid\Fusion\Log\Events\Infos\Name")
                    return new Name("");

                if ($class == "Valvoid\Fusion\Log\Events\Infos\Name")
                    return new Name("");

                if ($class == "task0") {
                    $mock = new TaskMock(...$args);
                    $tasks[$class] = $mock;
                    return $mock;
                }
            };

            $fusion = new Fusion(
                box: $this->box,
                file: $file,
                dir: $dir,
                config: []);

            $fusion->execute("test");

            foreach ($tasks as $class => $task)
                if (($class != "task0") ||
                    (!$task instanceof TaskMock) ||
                    $task->executes != 1 ||
                    $task->config != ["task" => $class]) {
                    $this->handleFailedTest();
                    break;
                }

            if ($is != [
                    "/#/fusion.json",
                    "/#/state/prefixes.php"] ||
                sizeof($tasks) != 1 ||
                $cGet != [["tasks", "test"]] || // group
                $delete != ["/#s", "/#t", "/#p", "/#o"] ||
                $load != [true])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }
}