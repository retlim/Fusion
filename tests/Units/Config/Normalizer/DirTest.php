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

namespace Valvoid\Fusion\Tests\Units\Config\Normalizer;

use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Config\Normalizer\Dir;
use Valvoid\Fusion\Config\Parser\Dir as DirParser;
use Valvoid\Fusion\Wrappers\Dir as DirWrapper;
use Valvoid\Fusion\Wrappers\System;
use Valvoid\Reflex\Test\Wrapper;

class DirTest extends Wrapper
{
    public function testUnixPathNormalization(): void
    {
        $config = [];
        $box = $this->createStub(Box::class);
        $dirWrapper = $this->createMock(DirWrapper::class);
        $bus = $this->createStub(Bus::class);
        $system = $this->createMock(System::class);
        $parser = $this->createMock(DirParser::class);
        $dir = new Dir(
            box: $box,
            dir: $dirWrapper,
            parser: $parser,
            bus: $bus,
            system: $system);

        $dirWrapper->fake("getCwd")
            ->return("#0");

        $parser->fake("getRootPath")
            ->expect(path: "#0")
            ->return("#1");

        $system->fake("getOsFamily")
            ->return("Linux") // just !== Windows
            ->fake("getEnvVariable")
            ->expect(name: "HOME")
            ->return("#2")
            ->expect(name: "XDG_CACHE_HOME")
            ->return(false)
            ->expect(name: "XDG_CONFIG_HOME")
            ->expect(name: "XDG_STATE_HOME");

        $dir->normalize($config, "i0");

        $this->validate($config)->as([
            "dir" => [
                "creatable" => true,
                "clearable" => false,
                "path" => "#1"
            ],
            "cache" => ["path" =>  "#2/.cache/i0"],
            "config" => ["path" =>  "#2/.config/i0"],
            "state" => ["path" =>  "#2/.local/state/i0"]
        ]);
    }

    public function testCustomUnixPathNormalization(): void
    {
        $config = [];
        $box = $this->recycleStub(Box::class);
        $dirWrapper = $this->recycleMock(DirWrapper::class);
        $bus = $this->recycleStub(Bus::class);
        $system = $this->createMock(System::class);
        $parser = $this->recycleMock(DirParser::class);
        $dir = new Dir(
            box: $box,
            dir: $dirWrapper,
            parser: $parser,
            bus: $bus,
            system: $system);

        $system->fake("getOsFamily")
            ->return("Linux")
            ->fake("getEnvVariable")
            ->expect(name: "HOME")
            ->return("#2")
            ->expect(name: "XDG_CACHE_HOME")
            ->return("#3")
            ->expect(name: "XDG_CONFIG_HOME")
            ->return("#4")
            ->expect(name: "XDG_STATE_HOME")
            ->return("#5");

        $dir->normalize($config, "i0");

        $this->validate($config)->as([
            "dir" => [
                "creatable" => true,
                "clearable" => false,
                "path" => "#1"
            ],
            "cache" => ["path" =>  "#3/i0"],
            "config" => ["path" =>  "#4/i0"],
            "state" => ["path" =>  "#5/i0"]
        ]);
    }

    public function testWindowsPathNormalization(): void
    {
        $config = [];
        $box = $this->recycleStub(Box::class);
        $dirWrapper = $this->recycleMock(DirWrapper::class);
        $bus = $this->recycleStub(Bus::class);
        $system = $this->createMock(System::class);
        $parser = $this->recycleMock(DirParser::class);
        $dir = new Dir(
            box: $box,
            dir: $dirWrapper,
            parser: $parser,
            bus: $bus,
            system: $system);

        $system->fake("getOsFamily")
            ->return("Windows")
            ->fake("getEnvVariable")
            ->expect(name: "LOCALAPPDATA")
            ->return("#2");

        $dir->normalize($config, "i0");

        $this->validate($config)->as([
            "dir" => [
                "creatable" => true,
                "clearable" => false,
                "path" => "#1"
            ],
            "cache" => ["path" =>  "#2/I0/cache"],
            "config" => ["path" =>  "#2/I0/config"],
            "state" => ["path" =>  "#2/I0/state"],
        ]);
    }

    public function testWindowsFallbackPathNormalization(): void
    {
        $config = [];
        $box = $this->recycleStub(Box::class);
        $dirWrapper = $this->recycleMock(DirWrapper::class);
        $bus = $this->recycleStub(Bus::class);
        $system = $this->createMock(System::class);
        $parser = $this->recycleMock(DirParser::class);
        $dir = new Dir(
            box: $box,
            dir: $dirWrapper,
            parser: $parser,
            bus: $bus,
            system: $system);

        $system->fake("getOsFamily")
            ->return("Windows")
            ->fake("getEnvVariable")
            ->expect(name: "LOCALAPPDATA")
            ->return(false)
            ->expect(name: "USERPROFILE")
            ->return("#2");

        $dir->normalize($config, "i0");

        $this->validate($config)->as([
            "dir" => [
                "creatable" => true,
                "clearable" => false,
                "path" => "#1"
            ],
            "cache" => ["path" =>  "#2/AppData/Local/I0/cache"],
            "config" => ["path" =>  "#2/AppData/Local/I0/config"],
            "state" => ["path" =>  "#2/AppData/Local/I0/state"]
        ]);
    }
}