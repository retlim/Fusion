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

namespace Valvoid\Fusion\Tests\Config\Parser\Dir;

use Throwable;
use Valvoid\Fusion\Config\Parser\Dir;
use Valvoid\Fusion\Tests\Config\Parser\Dir\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Config\Parser\Dir\Mocks\BusMock;
use Valvoid\Fusion\Tests\Config\Parser\Dir\Mocks\DirMock;
use Valvoid\Fusion\Tests\Config\Parser\Dir\Mocks\FileMock;
use Valvoid\Fusion\Tests\Test;

class DirTest extends Test
{
    protected string|array $coverage = Dir::class;
    private BoxMock $box;
    public function __construct()
    {
        $this->box = new BoxMock;

        $this->testParse();
        $this->testRootDir();

        $this->box::unsetInstance();
    }

    public function testParse(): void
    {
        try {
            $parser = new Dir(
                box: $this->box,
                dir: new DirMock,
                bus: new BusMock,
                file: new FileMock);

            $config["dir"]["path"] = "/d0/./d1\\d2/../d3";
            $parser->parse($config);

            if ($config["dir"]["path"] != "/d0/d1/d3")
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testRootDir(): void
    {
        try {
            $dir = new DirMock;
            $file = new FileMock;
            $bus = new BusMock;
            $parser = new Dir(
                box: $this->box,
                dir: $dir,
                bus: $bus,
                file: $file);

            $dirname =
            $is = [];
            $dir->dirname = function ($dir) use (&$dirname) {
                $dirname[] = $dir;

                if ($dir == "/d0/d1/d2")
                    return "/d0/d1";

                return "/d0";
            };

            $file->is = function ($file) use (&$is) {
                $is[] = $file;

                return $file == "/d0/fusion.json";
            };

            $path = $parser->getRootPath("/d0/d1/d2");

            if ($is != [
                    "/d0/d1/d2/fusion.json",
                    "/d0/d1/fusion.json",
                    "/d0/fusion.json"] ||
                $dirname != [
                    "/d0/d1/d2",
                    "/d0/d1",
                    "/d0"] ||
                $path !== "/d0")
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }
}