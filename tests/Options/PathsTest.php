<?php
/**
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

namespace Valvoid\Fusion\Tests\Options;

use Throwable;
use Valvoid\Fusion\Options\Paths;
use Valvoid\Fusion\Tests\Options\Mocks\DirMock;
use Valvoid\Fusion\Tests\Options\Mocks\FileMock;
use Valvoid\Fusion\Tests\Options\Mocks\SystemMock;
use Valvoid\Fusion\Tests\Test;

class PathsTest extends Test
{
    protected string|array $coverage = Paths::class;

    public function __construct()
    {
        $this->testOwnIdentifier();
        $this->testVariationIdentifier();
        $this->testFallbackPaths();
        $this->testWindowsFallbackPaths();
        $this->testWindowsPaths();
    }

    public function testWindowsFallbackPaths(): void
    {
        try {
            $system = new SystemMock;
            $file = new FileMock;
            $dir = new DirMock;
            $env =
            $is = [];
            $dir->dirname = function () {
                return "/d0/d1/d2";
            };

            $system->getOsFamily = function () {return "Windows";};
            $system->getEnvVariable = function ($name) use (&$env) {
                $env[] = $name;

                if ($name == "LOCALAPPDATA") return false;
                if ($name == "USERPROFILE") return "/#";

                return "";
            };

            $file->is = function ($file) use (&$is) {
                $is[] = $file;
                return $file == "/d0/d1/d2/fusion.json";
            };

            $paths = new Paths($dir, $file, $system);

            if ($is != ["/d0/d1/d2/fusion.json"] ||
                $env != [
                    "LOCALAPPDATA",
                    "USERPROFILE"] ||
                $paths->path !== "/d0/d1/d2" ||
                $paths->cache !== "/#/AppData/Local/Valvoid/Fusion/cache" ||
                $paths->config !== "/#/AppData/Local/Valvoid/Fusion/config" ||
                $paths->state !== "/#/AppData/Local/Valvoid/Fusion/state")
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testWindowsPaths(): void
    {
        try {
            $system = new SystemMock;
            $file = new FileMock;
            $dir = new DirMock;
            $env =
            $is = [];
            $dir->dirname = function () {
                return "/d0/d1/d2";
            };

            $system->getOsFamily = function () {return "Windows";};
            $system->getEnvVariable = function ($name) use (&$env) {
                $env[] = $name;

                if ($name == "LOCALAPPDATA") return "/#";

                return "";
            };

            $file->is = function ($file) use (&$is) {
                $is[] = $file;
                return $file == "/d0/d1/d2/fusion.json";
            };

            $paths = new Paths($dir, $file, $system);

            if ($is != ["/d0/d1/d2/fusion.json"] ||
                $env != ["LOCALAPPDATA"] ||
                $paths->path !== "/d0/d1/d2" ||
                $paths->cache !== "/#/Valvoid/Fusion/cache" ||
                $paths->config !== "/#/Valvoid/Fusion/config" ||
                $paths->state !== "/#/Valvoid/Fusion/state")
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testFallbackPaths(): void
    {
        try {
            $system = new SystemMock;
            $file = new FileMock;
            $dir = new DirMock;
            $env =
            $is = [];
            $dir->dirname = function () {
                return "/d0/d1/d2";
            };

            $system->getOsFamily = function () {return "Linux";};
            $system->getEnvVariable = function ($name) use (&$env) {
                $env[] = $name;

                if ($name == "HOME") return "/#";
                if ($name == "XDG_CACHE_HOME") return false;
                if ($name == "XDG_CONFIG_HOME") return false;
                if ($name == "XDG_STATE_HOME") return false;

                return "";
            };

            $file->is = function ($file) use (&$is) {
                $is[] = $file;
                return $file == "/d0/d1/d2/fusion.json";
            };

            $paths = new Paths($dir, $file, $system);

            if ($is != ["/d0/d1/d2/fusion.json"] ||
                $env != [
                    "HOME",
                    "XDG_CACHE_HOME",
                    "XDG_CONFIG_HOME",
                    "XDG_STATE_HOME"] ||
                $paths->path !== "/d0/d1/d2" ||
                $paths->cache !== "/#/.cache/valvoid/fusion" ||
                $paths->config !== "/#/.config/valvoid/fusion" ||
                $paths->state !== "/#/.local/state/valvoid/fusion")
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testOwnIdentifier(): void
    {
        try {
            $system = new SystemMock;
            $file = new FileMock;
            $dir = new DirMock;
            $env =
            $is = [];
            $dir->dirname = function () {
                return "/d0/d1/d2";
            };

            $system->getOsFamily = function () {return "Linux";};
            $system->getEnvVariable = function ($name) use (&$env) {
                $env[] = $name;

                if ($name == "HOME") return "/#";
                if ($name == "XDG_CACHE_HOME") return "/#ca";
                if ($name == "XDG_CONFIG_HOME") return "/#co";
                if ($name == "XDG_STATE_HOME") return "/#s";

                return "";
            };

            $file->is = function ($file) use (&$is) {
                $is[] = $file;
                return $file == "/d0/d1/d2/fusion.json";
            };

            $paths = new Paths($dir, $file, $system);

            if ($is != ["/d0/d1/d2/fusion.json"] ||
                $env != [
                    "HOME",
                    "XDG_CACHE_HOME",
                    "XDG_CONFIG_HOME",
                    "XDG_STATE_HOME"] ||
                $paths->path !== "/d0/d1/d2" ||
                $paths->cache !== "/#ca/valvoid/fusion" ||
                $paths->config !== "/#co/valvoid/fusion" ||
                $paths->state !== "/#s/valvoid/fusion")
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testVariationIdentifier(): void
    {
        try {
            $system = new SystemMock;
            $file = new FileMock;
            $dir = new DirMock;
            $dirname =
            $get =
            $env =
            $is = [];
            $dir->dirname = function ($file, $levels) use (&$dirname) {
                if ($levels == 2)
                    return "/d0/d1/d2/d3";

                $dirname[] = $file;

                if ($file == "/d0/d1/d2/d3")
                    return "/d0/d1/d2";

                return "/d0/d1";
            };

            $system->getOsFamily = function () {return "Linux";};
            $system->getEnvVariable = function ($name) use (&$env) {
                $env[] = $name;

                if ($name == "HOME") return "/#";
                if ($name == "XDG_CACHE_HOME") return "/#ca";
                if ($name == "XDG_CONFIG_HOME") return "/#co";
                if ($name == "XDG_STATE_HOME") return "/#s";

                return "";
            };

            $file->is = function ($file) use (&$is) {
                $is[] = $file;
                return $file == "/d0/d1/fusion.json";
            };

            $file->get = function ($file) use (&$get) {
                $get[] = $file;
                return "{\"id\": \"i0/i0\"}";
            };

            $paths = new Paths($dir, $file, $system);

            if ($get != ["/d0/d1/fusion.json"] ||
                $is != [
                    "/d0/d1/d2/d3/fusion.json",
                    "/d0/d1/d2/fusion.json",
                    "/d0/d1/fusion.json"] ||
                $dirname != [
                    "/d0/d1/d2/d3",
                    "/d0/d1/d2",
                    "/d0/d1"] ||
                $env != [
                    "HOME",
                    "XDG_CACHE_HOME",
                    "XDG_CONFIG_HOME",
                    "XDG_STATE_HOME"] ||
                $paths->path !== "/d0/d1" ||
                $paths->cache !== "/#ca/i0/i0" ||
                $paths->config !== "/#co/i0/i0" ||
                $paths->state !== "/#s/i0/i0")
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }
}