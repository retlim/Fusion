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

namespace Valvoid\Fusion\Tests\Settings;

use Throwable;
use Valvoid\Fusion\Settings\Config;
use Valvoid\Fusion\Tests\Options\Mocks\DirMock;
use Valvoid\Fusion\Tests\Options\Mocks\FileMock;
use Valvoid\Fusion\Tests\Options\Mocks\SystemMock;
use Valvoid\Fusion\Tests\Test;

class ConfigTest extends Test
{
    protected string|array $coverage = Config::class;

    public function __construct()
    {
        $this->testFile();
        $this->testVariationFile();
        $this->testFallbackPathFile();
        $this->testWindowsFile();
        $this->testWindowsFallbackPathFile();
        $this->testGetContent();
        $this->testPersist();
    }

    public function testFile(): void
    {
        try {
            $env =
            $is = [];
            $dir = new DirMock;
            $dir->dirname = function () {
                return "/d0/d1/d2";
            };

            $file = new FileMock;
            $file->is = function ($file) use (&$is) {
                $is[] = $file;
                return $file == "/d0/d1/d2/fusion.json";
            };

            $system = new SystemMock;
            $system->getOsFamily = fn() => "Linux";
            $system->getEnvVariable = function ($name) use (&$env) {
                $env[] = $name;

                if ($name == "XDG_CONFIG_HOME") return "/#co";

                return "";
            };
            $config = new Config(
                dir: $dir,
                fileWrapper: $file,
                system: $system
            );

            if ($config->file != "/#co/valvoid/fusion/config.json" ||
                $is != ["/d0/d1/d2/fusion.json"] ||
                $env != ["XDG_CONFIG_HOME"])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testFallbackPathFile(): void
    {
        try {
            $env =
            $is = [];
            $dir = new DirMock;
            $dir->dirname = function () {
                return "/d0/d1/d2";
            };

            $file = new FileMock;
            $file->is = function ($file) use (&$is) {
                $is[] = $file;
                return $file == "/d0/d1/d2/fusion.json";
            };

            $system = new SystemMock;
            $system->getOsFamily = fn() => "Linux";
            $system->getEnvVariable = function ($name) use (&$env) {
                $env[] = $name;

                if ($name == "HOME") return "/#";
                if ($name == "XDG_CONFIG_HOME") return false;

                return "";
            };
            $config = new Config(
                dir: $dir,
                fileWrapper: $file,
                system: $system
            );

            if ($config->file != "/#/.config/valvoid/fusion/config.json" ||
                $is != ["/d0/d1/d2/fusion.json"] ||
                $env != ["XDG_CONFIG_HOME", "HOME"])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testVariationFile(): void
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

                if ($name == "XDG_CONFIG_HOME") return "/#co";

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

            $config = new Config($dir, $file, $system);

            if ($get != ["/d0/d1/fusion.json"] ||
                $is != [
                    "/d0/d1/d2/d3/fusion.json",
                    "/d0/d1/d2/fusion.json",
                    "/d0/d1/fusion.json"] ||
                $dirname != [
                    "/d0/d1/d2/d3",
                    "/d0/d1/d2",
                    "/d0/d1"] ||
                $env != ["XDG_CONFIG_HOME"] ||
                $config->file !== "/#co/i0/i0/config.json")
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testWindowsFile(): void
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

            $config = new Config($dir, $file, $system);

            if ($is != ["/d0/d1/d2/fusion.json"] ||
                $env != ["LOCALAPPDATA"] ||
                $config->file !== "/#/Valvoid/Fusion/config/config.json")
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testWindowsFallbackPathFile(): void
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

            $config = new Config($dir, $file, $system);

            if ($is != ["/d0/d1/d2/fusion.json"] ||
                $env != [
                    "LOCALAPPDATA",
                    "USERPROFILE"] ||
                $config->file !== "/#/AppData/Local/Valvoid/Fusion/config/config.json")
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testGetContent(): void
    {
        try {
            $env = $exists = $get =
            $is = [];
            $dir = new DirMock;
            $dir->dirname = function () {
                return "/d0/d1/d2";
            };

            $file = new FileMock;
            $file->is = function ($file) use (&$is) {
                $is[] = $file;
                return $file == "/d0/d1/d2/fusion.json";
            };

            $file->exists = function ($file) use (&$exists) {
                $exists[] = $file;
                return $file == "/#co/valvoid/fusion/config.json";
            };

            $file->get = function ($file) use (&$get) {
                $get[] = $file;
                if ($file == "/#co/valvoid/fusion/config.json")
                    return "###";
            };

            $system = new SystemMock;
            $system->getOsFamily = fn() => "Linux";
            $system->getEnvVariable = function ($name) use (&$env) {
                $env[] = $name;

                if ($name == "XDG_CONFIG_HOME") return "/#co";

                return "";
            };
            $config = new Config(
                dir: $dir,
                fileWrapper: $file,
                system: $system
            );

            $content = $config->getContent();

            if ($content != "###" ||
                $get != ["/#co/valvoid/fusion/config.json"] ||
                $exists != ["/#co/valvoid/fusion/config.json"] ||
                $config->file != "/#co/valvoid/fusion/config.json" ||
                $is != ["/d0/d1/d2/fusion.json"] ||
                $env != ["XDG_CONFIG_HOME"])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testPersist(): void
    {
        try {
            $env =
            $exists =
            $get =
            $put =
            $is = [];
            $dir = new DirMock;
            $dir->dirname = function () {
                return "/d0/d1/d2";
            };

            $file = new FileMock;
            $file->is = function ($file) use (&$is) {
                $is[] = $file;
                return $file == "/d0/d1/d2/fusion.json";
            };

            $file->exists = function ($file) use (&$exists) {
                $exists[] = $file;
                return $file == "/#co/valvoid/fusion/config.json";
            };

            $file->get = function ($file) use (&$get) {
                $get[] = $file;
                if ($file == "/#co/valvoid/fusion/config.json")
                    return "{\"k0\": \"v0\"}";
            };

            $file->put = function ($file, $data) use (&$put) {
                $put[] = [
                    "file" => $file,
                    "data" => preg_replace("/\s+/", "", $data)
                ];

                if ($file == "/#co/valvoid/fusion/config.json")
                    return 1;
            };

            $system = new SystemMock;
            $system->getOsFamily = fn() => "Linux";
            $system->getEnvVariable = function ($name) use (&$env) {
                $env[] = $name;

                if ($name == "XDG_CONFIG_HOME") return "/#co";

                return "";
            };
            $config = new Config(
                dir: $dir,
                fileWrapper: $file,
                system: $system
            );

            $config->persist(["k1" => "v1", "k0" => "v2"]);

            if ($put != [[
                    "file" => "/#co/valvoid/fusion/config.json",
                    "data" =>  "{\"k0\":\"v2\",\"k1\":\"v1\"}"
                ]] ||
                $get != ["/#co/valvoid/fusion/config.json"] ||
                $exists != ["/#co/valvoid/fusion/config.json"] ||
                $config->file != "/#co/valvoid/fusion/config.json" ||
                $is != ["/d0/d1/d2/fusion.json"] ||
                $env != ["XDG_CONFIG_HOME"])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }
}