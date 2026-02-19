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

namespace Valvoid\Fusion\Tests\Units\Options;

use Valvoid\Fusion\Options\Paths;
use Valvoid\Fusion\Wrappers\Dir;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Fusion\Wrappers\System;
use Valvoid\Reflex\Test\Wrapper;

class PathsTest extends Wrapper
{
    public function testFallbackWindowsPaths(): void
    {
        $file = $this->createMock(File::class);
        $dir = $this->createMock(Dir::class);
        $system = $this->createMock(System::class);

        $dir->fake("getDirname")
            ->hook(function ($path, $levels) {
                $this->validate(dirname($path, $levels))
                    ->as(dirname(__DIR__, 3));

                return "#0";
            });

        $file->fake("is")
            ->expect(file:  "#0/fusion.json")
            ->return(true);

        $system->fake("getOsFamily")
            ->return("Windows")
            ->fake("getEnvVariable")
            ->expect(name: "LOCALAPPDATA")
            ->return(false)
            ->expect(name: "USERPROFILE")
            ->return("#1");

        $paths = new Paths($dir, $file, $system);
        $prefix = "#1/AppData/Local/Valvoid/Fusion";

        $this->validate($paths->path)
            ->as("#0");

        $this->validate($paths->cache)
            ->as("$prefix/cache");

        $this->validate($paths->config)
            ->as("$prefix/config");

        $this->validate($paths->state)
            ->as("$prefix/state");
    }

    public function testWindowsPaths(): void
    {
        $file = $this->recycleMock(File::class);
        $dir = $this->recycleMock(Dir::class);
        $system = $this->recycleMock(System::class);

        $system->fake("getEnvVariable")
            ->expect(name: "LOCALAPPDATA")
            ->return("#1");

        $paths = new Paths($dir, $file, $system);
        $prefix = "#1/Valvoid/Fusion";

        $this->validate($paths->path)
            ->as("#0");

        $this->validate($paths->cache)
            ->as("$prefix/cache");

        $this->validate($paths->config)
            ->as("$prefix/config");

        $this->validate($paths->state)
            ->as("$prefix/state");
    }

    public function testDefaultLinuxPaths(): void
    {
        $file = $this->recycleMock(File::class);
        $dir = $this->recycleMock(Dir::class);
        $system = $this->createMock(System::class);

        $system->fake("getOsFamily")
            ->return("Linux")
            ->fake("getEnvVariable")
            ->expect(name: "HOME")
            ->return("#1")
            ->expect(name: "XDG_CACHE_HOME")
            ->return(false)
            ->expect(name: "XDG_CONFIG_HOME")
            ->expect(name: "XDG_STATE_HOME");

        $paths = new Paths($dir, $file, $system);
        $suffix = "valvoid/fusion";

        $this->validate($paths->path)
            ->as("#0");

        $this->validate($paths->cache)
            ->as("#1/.cache/$suffix");

        $this->validate($paths->config)
            ->as("#1/.config/$suffix");

        $this->validate($paths->state)
            ->as("#1/.local/state/$suffix");
    }

    public function testCustomLinuxPaths(): void
    {
        $file = $this->recycleMock(File::class);
        $dir = $this->recycleMock(Dir::class);
        $system = $this->recycleMock(System::class);

        $system->fake("getEnvVariable")
            ->expect(name: "HOME")
            ->return("#1")
            ->expect(name: "XDG_CACHE_HOME")
            ->return("#2")
            ->expect(name: "XDG_CONFIG_HOME")
            ->return("#3")
            ->expect(name: "XDG_STATE_HOME")
            ->return("#4");

        // default identifier
        // supports multiple package manager with own identifier
        $suffix = "valvoid/fusion";
        $paths = new Paths($dir, $file, $system);

        $this->validate($paths->path)
            ->as("#0");

        $this->validate($paths->cache)
            ->as("#2/$suffix");

        $this->validate($paths->config)
            ->as("#3/$suffix");

        $this->validate($paths->state)
            ->as("#4/$suffix");
    }

    public function testCustomPackageManagerIdentifier(): void
    {
        $file = $this->createMock(File::class);
        $dir = $this->createMock(Dir::class);
        $system = $this->recycleMock(System::class);

        $dir->fake("getDirname")
            ->hook(function ($path, $levels) {
                $this->validate(dirname($path, $levels))
                    ->as(dirname(__DIR__, 3));

                return "#000";
            })
            ->expect(path: "#000", levels: 1)
            ->return("#00")
            ->expect(path: "#00", levels: 1)
            ->return("#0");

        $file->fake("is")
            ->expect(file: "#000/fusion.json")
            ->return(false)
            ->expect(file: "#00/fusion.json")
            ->expect(file: "#0/fusion.json")
            ->return(true)
            ->fake("get")
            ->return('{"id": "i0/i0"}');

        // custom identifier
        $suffix = "i0/i0";
        $paths = new Paths($dir, $file, $system);

        $this->validate($paths->path)
            ->as("#0");

        $this->validate($paths->cache)
            ->as("#2/$suffix");

        $this->validate($paths->config)
            ->as("#3/$suffix");

        $this->validate($paths->state)
            ->as("#4/$suffix");
    }
}