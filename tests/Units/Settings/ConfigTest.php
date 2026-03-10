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

namespace Valvoid\Fusion\Tests\Units\Settings;

use Valvoid\Fusion\Settings\Config;
use Valvoid\Fusion\Wrappers\Dir;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Fusion\Wrappers\System;
use Valvoid\Reflex\Test\Wrapper;

class ConfigTest extends Wrapper
{
    public function testFile(): void
    {
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $system = $this->createMock(System::class);

        $dir->fake("getDirname")
            ->return("#0");

        $file->fake("is")
            ->expect(file: "#0/fusion.json")
            ->return(true);

        $system->fake("getOsFamily")
            ->return("Linux")
            ->fake("getEnvVariable")
            ->expect(name: "XDG_CONFIG_HOME")
            ->return("#1");

        $config = new Config(
            dir: $dir,
            fileWrapper: $file,
            system: $system
        );

        $this->validate($config->file)
            ->as("#1/valvoid/fusion/config.json");
    }

    public function testFallbackPathFile(): void
    {
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $system = $this->createMock(System::class);

        $dir->fake("getDirname")
            ->return("#0");

        $file->fake("is")
            ->expect(file: "#0/fusion.json")
            ->return(true);

        $system->fake("getOsFamily")
            ->return("Linux")
            ->fake("getEnvVariable")
            ->expect(name: "XDG_CONFIG_HOME")
            ->return(false)
            ->expect(name: "HOME")
            ->return("#1");

        $config = new Config(
            dir: $dir,
            fileWrapper: $file,
            system: $system
        );

        $this->validate($config->file)
            ->as("#1/.config/valvoid/fusion/config.json");
    }

    public function testVariationFile(): void
    {
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $system = $this->createMock(System::class);

        $dir->fake("getDirname")
            ->expect(levels: 2)
            ->return("#0")
            ->expect(path: "#0")
            ->return("#1")
            ->expect(path: "#1")
            ->return("#2");

        $file->fake("is")
            ->expect(file: "#0/fusion.json")
            ->return(false)
            ->expect(file: "#1/fusion.json")
            ->expect(file: "#2/fusion.json")
            ->return(true)
            ->fake("get")
            ->expect(file: "#2/fusion.json")
            ->return('{"id": "i0/i0"}');

        $system->fake("getOsFamily")
            ->return("Linux")
            ->fake("getEnvVariable")
            ->expect(name: "XDG_CONFIG_HOME")
            ->return("#3");

        $config = new Config(
            dir: $dir,
            fileWrapper: $file,
            system: $system
        );

        $this->validate($config->file)
            ->as("#3/i0/i0/config.json");
    }

    public function testWindowsFile(): void
    {
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $system = $this->createMock(System::class);

        $dir->fake("getDirname")
            ->return("#0");

        $file->fake("is")
            ->expect(file: "#0/fusion.json")
            ->return(true);

        $system->fake("getOsFamily")
            ->return("Windows")
            ->fake("getEnvVariable")
            ->expect(name: "LOCALAPPDATA")
            ->return("#1");

        $config = new Config(
            dir: $dir,
            fileWrapper: $file,
            system: $system
        );

        $this->validate($config->file)
            ->as("#1/Valvoid/Fusion/config/config.json");
    }

    public function testWindowsFallbackPathFile(): void
    {
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $system = $this->createMock(System::class);

        $dir->fake("getDirname")
            ->return("#0");

        $file->fake("is")
            ->expect(file: "#0/fusion.json")
            ->return(true);

        $system->fake("getOsFamily")
            ->return("Windows")
            ->fake("getEnvVariable")
            ->expect(name: "LOCALAPPDATA")
            ->return(false)
            ->expect(name: "USERPROFILE")
            ->return("#1");

        $config = new Config(
            dir: $dir,
            fileWrapper: $file,
            system: $system
        );

        $this->validate($config->file)
            ->as("#1/AppData/Local/Valvoid/Fusion/config/config.json");
    }

    public function testGetContent(): void
    {
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $system = $this->createMock(System::class);

        $dir->fake("getDirname")
            ->return("#0");

        $file->fake("is")
            ->expect(file: "#0/fusion.json")
            ->return(true)
            ->fake("exists")
            ->expect(file: "#1/valvoid/fusion/config.json")
            ->return(true)
            ->fake("get")
            ->expect(file: "#1/valvoid/fusion/config.json")
            ->return("###");

        $system->fake("getOsFamily")
            ->return("Linux")
            ->fake("getEnvVariable")
            ->expect(name: "XDG_CONFIG_HOME")
            ->return("#1");

        $config = new Config(
            dir: $dir,
            fileWrapper: $file,
            system: $system
        );

        $this->validate($config->getContent())
            ->as("###");

        $this->validate($config->file)
            ->as("#1/valvoid/fusion/config.json");
    }

    public function testPersist(): void
    {
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $system = $this->createMock(System::class);

        $dir->fake("getDirname")
            ->return("#0");

        $file->fake("is")
            ->expect(file: "#0/fusion.json")
            ->return(true)
            ->fake("exists")
            ->expect(file: "#1/valvoid/fusion/config.json")
            ->return(true)
            ->fake("get")
            ->expect(file: "#1/valvoid/fusion/config.json")
            ->return('{"k0": "v0"}')
            ->fake("put")
            ->return(1)
            ->expect(file: "#1/valvoid/fusion/config.json",
                data: json_encode(["k0" => "v2", "k1" => "v1"], JSON_PRETTY_PRINT|
                    JSON_UNESCAPED_SLASHES));

        $system->fake("getOsFamily")
            ->return("Linux")
            ->fake("getEnvVariable")
            ->expect(name: "XDG_CONFIG_HOME")
            ->return("#1");

        $config = new Config(
            dir: $dir,
            fileWrapper: $file,
            system: $system
        );

        $config->persist(["k1" => "v1", "k0" => "v2"]);

        $this->validate($config->file)
            ->as("#1/valvoid/fusion/config.json");
    }
}