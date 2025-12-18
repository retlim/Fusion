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

namespace Valvoid\Fusion\Tests\Dir;

use Valvoid\Fusion\Bus\Events\Cache;
use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Tests\Dir\Mocks\BusMock;
use Valvoid\Fusion\Tests\Dir\Mocks\ConfigMock;
use Valvoid\Fusion\Tests\Dir\Mocks\DirMock;
use Valvoid\Fusion\Tests\Dir\Mocks\FileMock;
use Valvoid\Fusion\Tests\Test;
use Valvoid\Fusion\Wrappers\Dir as DirWrapper;
use Valvoid\Fusion\Wrappers\File;

class DirTest extends Test
{
    protected string|array $coverage = [
        Dir::class,

        // ballast
        File::class,
        DirWrapper::class
    ];

    protected Dir $directory;
    protected BusMock $bus;
    protected DirMock $dir;
    protected FileMock $file;
    private ConfigMock $config;
    public function __construct()
    {
        $this->bus = new BusMock;
        $this->dir = new DirMock;
        $this->file = new FileMock;
        $this->config = new ConfigMock;

        $this->testRecycleContent();
        $this->testGetCacheDir();
        $this->testGetOtherDir();
        $this->testGetStateDir();
        $this->testGetHubDir();
        $this->testGetLogDir();
        $this->testGetPackagesDir();
        $this->testGetTaskDir();
        $this->testGetRootDir();

        $this->testCreateDir();
        $this->testCopy();
        $this->testRename();
        $this->testDelete();
        $this->testClear();

        $this->testRecycleEmptyContent();
        $this->testReplaceContent();
        $this->testCreateContent();
        $this->testMissingCreatContentAuthorizationError();
        $this->testNewCacheDir();
    }

    public function testCreateDir(): void
    {
        $this->file->exists = function ($file) {
            if ($file != "#1")
                $this->handleFailedTest();

            return false;
        };

        $this->dir->create = function ($dir, $permissions) {
            if ($dir != "#1" || $permissions != 123)
                $this->handleFailedTest();

            return true;
        };

        $this->directory->createDir("#1", 123);
    }

    public function testRename(): void
    {
        $this->file->is = function ($to) {
            if ($to != "#2")
                $this->handleFailedTest();

            return true;
        };

        $this->file->unlink = function ($to) {
            if ($to != "#2")
                $this->handleFailedTest();

            return true;
        };

        $this->dir->rename = function ($from, $to) {
            if ($from != "#1" ||
                $to != "#2")
                $this->handleFailedTest();

            return true;
        };

        $this->directory->rename("#1", "#2");

        $this->file->is = fn () => false;

        $this->dir->is = function ($to) {
            if ($to != "#2")
                $this->handleFailedTest();

            return true;
        };

        $this->dir->delete = function ($to) {
            if ($to != "#2")
                $this->handleFailedTest();

            return true;
        };

        $this->dir->rename = fn () => true;

        $this->directory->rename("#1", "#2");
    }

    public function testCopy(): void
    {
        $this->file->copy = function ($from, $to) {
            if ($from != "#1" || $to != "#2")
                $this->handleFailedTest();

            return true;
        };

        $this->directory->copy("#1", "#2");
    }

    public function testDelete(): void
    {
        $dirs =
        $filenames =
        $deletes =
        $files =
        $unlinks = [];

        $this->dir->is = function ($dir) use (&$dirs) {
            $dirs[] = $dir;

            return $dir == "d0" || $dir == "d0/d1";
        };

        $this->dir->filenames = function ($dir) use (&$filenames) {
            $filenames[] = $dir;

            if ($dir == "d0")
                return ["d1", "f0", "f1"];

            if ($dir == "d0/d1")
                return ["f2"];

            return [];
        };

        $this->file->is = function ($file) use (&$files) {
            $files[] = $file;

            return $file == "d0/f0" || $file == "d0/f1" || $file == "d0/d1/f2";
        };

        $this->file->unlink = function ($file) use (&$unlinks) {
            $unlinks[] = $file;

            return true;
        };

        $this->dir->delete = function ($file) use (&$deletes) {
            $deletes[] = $file;

            return true;
        };

        $this->directory->delete("d0");

        if ($dirs != ["d0", "d0/d1", "d0/d1/f2", "d0/f0", "d0/f1"] ||
            $filenames != ["d0", "d0/d1"] ||
            $deletes != ["d0/d1", "d0"] ||
            $files != ["d0/d1/f2", "d0/f0", "d0/f1"] ||
            $unlinks != ["d0/d1/f2", "d0/f0", "d0/f1"])
            $this->handleFailedTest();
    }

    public function testClear(): void
    {
        $dirs =
        $filenames =
        $deletes = [];

        $this->dir->is = function ($dir) use (&$dirs) {
            $dirs[] = $dir;

            return true;
        };

        $this->dir->filenames = function ($dir) use (&$filenames) {
            $filenames[] = $dir;

            return [];
        };

        $this->dir->delete = function ($dir) use (&$deletes) {
            $deletes[] = $dir;

            return true;
        };

        $this->directory->clear("d0", "/d1/d2/d3");

        if ($dirs != ["d0/d1/d2/d3", "d0/d1/d2", "d0/d1"] ||
            $filenames != ["d0/d1/d2/d3", "d0/d1/d2", "d0/d1"] ||
            $deletes != ["d0/d1/d2/d3", "d0/d1/d2", "d0/d1"])
            $this->handleFailedTest();
    }

    public function testGetTaskDir(): void
    {
        if ($this->directory->getTaskDir() != "/#s/task")
            $this->handleFailedTest();
    }

    public function testGetHubDir(): void
    {
        if ($this->directory->getHubDir() != "/#c/hub")
            $this->handleFailedTest();
    }

    public function testGetLogDir(): void
    {
        if ($this->directory->getLogDir() != "/#s/log")
            $this->handleFailedTest();
    }

    public function testGetStateDir(): void
    {
        if ($this->directory->getStateDir() != "/#s/state")
            $this->handleFailedTest();
    }

    public function testGetCacheDir(): void
    {
        if ($this->directory->getCacheDir() != "/#/c")
            $this->handleFailedTest();
    }

    public function testGetOtherDir(): void
    {
        if ($this->directory->getOtherDir() != "/#s/other")
            $this->handleFailedTest();
    }

    public function testGetPackagesDir(): void
    {
        if ($this->directory->getPackagesDir() != "/#s/packages")
            $this->handleFailedTest();
    }

    public function testGetRootDir(): void
    {
        if ($this->directory->getRootDir() != "/#")
            $this->handleFailedTest();
    }

    public function testNewCacheDir(): void
    {
        $this->dir->is = fn () => false;
        $this->dir->create = fn () => true;
        $this->file->copy = fn () => true;
        $this->config->get = function (...$breadcrumb) {
            if ($breadcrumb === ["state", "path"])
                return "/#s";
            if ($breadcrumb === ["cache", "path"])
                return "/#c";
            if ($breadcrumb === ["dir", "path"])
                return "/#";
            if ($breadcrumb === ["dir", "creatable"])
                return true;
        };

        $this->directory = new Dir(dir: $this->dir,
            file: $this->file,
            bus: $this->bus,
            config: $this->config);

        $oldCache = $this->directory->getCacheDir();
        $newCache = "$oldCache/newCachePath";

        call_user_func($this->bus->callback, new Cache($newCache));

        if ($this->directory->getCacheDir() != $newCache ||
            $oldCache != "/#/state")
            $this->handleFailedTest();
    }

    public function testRecycleContent(): void
    {
        $this->dir->is = function ($dir) {
            if ($dir != "/#")
                $this->handleFailedTest();

            return true;
        };

        $this->file->exists = function ($file) {
            if ($file != "/#/fusion.json")
                $this->handleFailedTest();

            return true;
        };

        $this->file->get = function ($file) {
            if ($file != "/#/fusion.json")
                $this->handleFailedTest();

            return json_encode(["structure" => [
                "/c" => "stateful"
            ]]);
        };
        $this->config->get = function (...$breadcrumb) {
            if ($breadcrumb === ["state", "path"])
                return "/#s";
            if ($breadcrumb === ["cache", "path"])
                return "/#c";
            if ($breadcrumb === ["dir", "path"])
                return "/#";
            if ($breadcrumb === ["dir", "clearable"])
                return false;
        };
        $this->directory = new Dir(dir: $this->dir,
            file: $this->file,
            bus: $this->bus,
            config: $this->config);

        if ($this->directory->getRootDir() != "/#" ||
            $this->directory->getCacheDir() != "/#/c")
            $this->handleFailedTest();
    }

    public function testRecycleEmptyContent(): void
    {
        $this->dir->is = function ($dir) {
            if ($dir != "/#")
                $this->handleFailedTest();

            return true;
        };

        $this->dir->filenames = function ($dir) {
            if ($dir != "/#")
                $this->handleFailedTest();

            return [];
        };

        $this->file->exists = function ($file) {
            if ($file != "/#/fusion.json")
                $this->handleFailedTest();

            return false;
        };

        $this->file->copy = function ($from, $to) {
            if ($from != dirname(__DIR__, 2) . "/src/Dir/placeholder.json" ||
                $to != "/#/fusion.json")
                $this->handleFailedTest();

            // placeholder metadata copied
            return true;
        };
        $this->config->get = function (...$breadcrumb) {
            if ($breadcrumb === ["state", "path"])
                return "/#s";
            if ($breadcrumb === ["cache", "path"])
                return "/#c";
            if ($breadcrumb === ["dir", "path"])
                return "/#";
            if ($breadcrumb === ["dir", "clearable"])
                return false;
        };
        $this->directory = new Dir(dir: $this->dir,
            file: $this->file,
            bus: $this->bus,
            config: $this->config);

        if ($this->directory->getRootDir() != "/#" ||
            $this->directory->getCacheDir() != "/#/state")
            $this->handleFailedTest();
    }

    public function testReplaceContent(): void
    {
        $dirs =
        $filenames =
        $deletes =
        $files =
        $unlinks = [];

        $this->dir->is = function ($dir) use (&$dirs) {
            $dirs[] = $dir;

            return $dir == "/#" || $dir == "/#/f0";
        };

        $this->dir->filenames = function ($dir) use (&$filenames) {
            $filenames[] = $dir;

            return $dir == "/#" ?
                ["f0", "f1"] :
                [];
        };

        $this->dir->delete = function ($dir) use (&$deletes) {
            $deletes[] = $dir;

            return true;
        };

        $this->file->is = function ($file) use (&$files) {
            $files[] = $file;

            return $file == "/#/f1";
        };

        $this->file->unlink = function ($file) use (&$unlinks) {
            $unlinks[] = $file;

            return $file == "/#/f1";
        };

        $this->file->copy = function ($from, $to) {
            if ($from != dirname(__DIR__, 2) . "/src/Dir/placeholder.json" ||
                $to != "/#/fusion.json")
                $this->handleFailedTest();

            // placeholder metadata copied
            return true;
        };
        $this->config->get = function (...$breadcrumb) {
            if ($breadcrumb === ["state", "path"])
                return "/#s";
            if ($breadcrumb === ["cache", "path"])
                return "/#c";
            if ($breadcrumb === ["dir", "path"])
                return "/#";
            if ($breadcrumb === ["dir", "clearable"])
                return true;
        };
        $this->directory = new Dir(dir: $this->dir,
            file: $this->file,
            bus: $this->bus,
            config: $this->config);

        if ($this->directory->getRootDir() != "/#" ||
            $this->directory->getCacheDir() != "/#/state" ||
            $dirs != ["/#", "/#/f0", "/#/f1"] ||
            $filenames != ["/#", "/#/f0"] ||
            $deletes != ["/#/f0"] ||
            $files != ["/#/f1"] ||
            $unlinks != ["/#/f1"])
            $this->handleFailedTest();
    }

    public function testCreateContent(): void
    {
        $this->dir->is = function ($dir) {
            if ($dir != "/#")
                $this->handleFailedTest();

            // root package dir does not exist
            // fall through recycle and replace
            return false;
        };

        $this->dir->create = function ($dir, $permissions, $recursive) {
            if ($dir != "/#" ||
                $permissions != 0755 ||
                !$recursive)
                $this->handleFailedTest();

            // dir created
            return true;
        };

        $this->file->copy = function ($from, $to) {
            if ($from != dirname(__DIR__, 2) . "/src/Dir/placeholder.json" ||
                $to != "/#/fusion.json")
                $this->handleFailedTest();

            // placeholder metadata copied
            return true;
        };
        $this->config->get = function (...$breadcrumb) {
            if ($breadcrumb === ["state", "path"])
                return "/#s";
            if ($breadcrumb === ["cache", "path"])
                return "/#c";
            if ($breadcrumb === ["dir", "path"])
                return "/#";
            if ($breadcrumb === ["dir", "creatable"])
                return true;
        };
        $this->directory = new Dir(dir: $this->dir,
            file: $this->file,
            bus: $this->bus,
            config: $this->config);

        if ($this->directory->getRootDir() != "/#" ||
            $this->directory->getCacheDir() != "/#/state")
            $this->handleFailedTest();
    }

    public function testMissingCreatContentAuthorizationError(): void
    {
        try {
            $this->dir->is = function ($dir) {

                // validate defined path
                if ($dir != "/#")
                    $this->handleFailedTest();

                // root package dir does not exist
                // fall through recycle and replace
                return false;
            };
            $this->config->get = function (...$breadcrumb) {
                if ($breadcrumb === ["state", "path"])
                    return "/#s";
                if ($breadcrumb === ["cache", "path"])
                    return "/#c";
                if ($breadcrumb === ["dir", "path"])
                    return "/#";
                if ($breadcrumb === ["dir", "creatable"])
                    return false;
            };
            $this->directory = new Dir(dir: $this->dir,
                file: $this->file,
                bus: $this->bus,
                config: $this->config);

            $this->handleFailedTest();

        // assertion
        // can't create new dir
        } catch (Error) {}
    }
}