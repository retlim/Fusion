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

namespace Valvoid\Fusion\Tests\Config\Normalizer\Dir;

use Throwable;
use Valvoid\Fusion\Config\Normalizer\Dir;
use Valvoid\Fusion\Tests\Config\Normalizer\Dir\Mocks\BusMock;
use Valvoid\Fusion\Tests\Config\Normalizer\Dir\Mocks\DirMock;
use Valvoid\Fusion\Tests\Config\Normalizer\Dir\Mocks\ParserMock;
use Valvoid\Fusion\Tests\Config\Normalizer\Dir\Mocks\SystemMock;
use Valvoid\Fusion\Tests\Config\Parser\Dir\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Test;

class DirTest extends Test
{
    protected string|array $coverage = Dir::class;
    private BoxMock $box;
    public function __construct()
    {
        $this->box = new BoxMock;

        $this->testNormalization();
        $this->testFallbackPaths();
        $this->testWindowsPaths();
        $this->testWindowsFallbackPaths();

        $this->box::unsetInstance();
    }

    public function testWindowsFallbackPaths(): void
    {
        try {
            $dir = new DirMock;
            $bus = new BusMock;
            $parser = new ParserMock;
            $system = new SystemMock;
            $normalizer = new Dir(
                box: $this->box,
                dir: $dir,
                parser: $parser,
                bus: $bus,
                system: $system);

            $config =
            $env = [];
            $dir->cwd = function () {
                return "/d0/d1";
            };

            $parser->root = function () {
                return "/d0/d1";
            };

            $system->getOsFamily = function () {
                return "Windows";
            };

            $system->getEnvVariable = function ($name) use (&$env) {
                $env[] = $name;

                if ($name == "LOCALAPPDATA") return false;
                if ($name == "USERPROFILE") return "/#";

                return "";
            };

            $normalizer->normalize($config, "i0");

            if ($env != [
                    "LOCALAPPDATA",
                    "USERPROFILE"] ||
                $config != [
                    "cache" => ["path" => "/#/AppData/Local/I0/cache"],
                    "config" => ["path" => "/#/AppData/Local/I0/config"],
                    "state" => ["path" => "/#/AppData/Local/I0/state"],
                    "dir" => [
                        "creatable" => true,
                        "clearable" => false,
                        "path" => "/d0/d1"
                    ]

                ]) $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testWindowsPaths(): void
    {
        try {
            $dir = new DirMock;
            $bus = new BusMock;
            $parser = new ParserMock;
            $system = new SystemMock;
            $normalizer = new Dir(
                box: $this->box,
                dir: $dir,
                parser: $parser,
                bus: $bus,
                system: $system);

            $config =
            $env = [];
            $dir->cwd = function () {
                return "/d0/d1";
            };

            $parser->root = function () {
                return "/d0/d1";
            };

            $system->getOsFamily = function () {
                return "Windows";
            };

            $system->getEnvVariable = function ($name) use (&$env) {
                $env[] = $name;

                if ($name == "LOCALAPPDATA") return "/#";

                return "";
            };

            $normalizer->normalize($config, "i0");

            if ($env != ["LOCALAPPDATA"] ||
                $config != [
                    "cache" => ["path" => "/#/I0/cache"],
                    "config" => ["path" => "/#/I0/config"],
                    "state" => ["path" => "/#/I0/state"],
                    "dir" => [
                        "creatable" => true,
                        "clearable" => false,
                        "path" => "/d0/d1"
                    ]

                ]) $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testFallbackPaths(): void
    {
        try {
            $dir = new DirMock;
            $bus = new BusMock;
            $parser = new ParserMock;
            $system = new SystemMock;
            $normalizer = new Dir(
                box: $this->box,
                dir: $dir,
                parser: $parser,
                bus: $bus,
                system: $system);

            $config =
            $env = [];
            $dir->cwd = function () {
                return "/d0/d1";
            };

            $parser->root = function () {
                return "/d0/d1";
            };

            $system->getOsFamily = function () {
                return "Linux";
            };

            $system->getEnvVariable = function ($name) use (&$env) {
                $env[] = $name;

                if ($name == "HOME") return "/#";
                if ($name == "XDG_CACHE_HOME") return false;
                if ($name == "XDG_CONFIG_HOME") return false;
                if ($name == "XDG_STATE_HOME") return false;

                return "";
            };

            $normalizer->normalize($config, "i0");

            if ($env != [
                    "HOME",
                    "XDG_CACHE_HOME",
                    "XDG_CONFIG_HOME",
                    "XDG_STATE_HOME"] ||
                $config != [
                    "cache" => ["path" => "/#/.cache/i0"],
                    "config" => ["path" => "/#/.config/i0"],
                    "state" => ["path" => "/#/.local/state/i0"],
                    "dir" => [
                        "creatable" => true,
                        "clearable" => false,
                        "path" => "/d0/d1"
                    ]

                ]) $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testNormalization(): void
    {
        try {
            $dir = new DirMock;
            $bus = new BusMock;
            $parser = new ParserMock;
            $system = new SystemMock;
            $normalizer = new Dir(
                box: $this->box,
                dir: $dir,
                parser: $parser,
                bus: $bus,
                system: $system);

            $config =
            $env = [];
            $dir->cwd = function () {
                return "/d0/d1";
            };

            $parser->root = function () {
                return "/d0/d1";
            };

            $system->getOsFamily = function () {
                return "Linux";
            };

            $system->getEnvVariable = function ($name) use (&$env) {
                $env[] = $name;

                if ($name == "HOME") return "/#";
                if ($name == "XDG_CACHE_HOME") return "/#ca";
                if ($name == "XDG_CONFIG_HOME") return "/#co";
                if ($name == "XDG_STATE_HOME") return "/#s";

                return "";
            };

            $normalizer->normalize($config, "i0");

            if ($env != [
                    "HOME",
                    "XDG_CACHE_HOME",
                    "XDG_CONFIG_HOME",
                    "XDG_STATE_HOME"] ||
                $config != [
                    "cache" => ["path" => "/#ca/i0"],
                    "config" => ["path" => "/#co/i0"],
                    "state" => ["path" => "/#s/i0"],
                    "dir" => [
                        "creatable" => true,
                        "clearable" => false,
                        "path" => "/d0/d1"
                    ]

                ]) $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }
}