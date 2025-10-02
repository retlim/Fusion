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
 */

namespace Valvoid\Fusion\Tests\Tasks\Shift\Mocks;

use Closure;
use Valvoid\Fusion\Dir\Proxy;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class DirectoryMock implements Proxy
{
    public Closure $root;
    public Closure $other;
    public Closure $state;
    public Closure $task;
    public Closure $cache;
    public Closure $packages;
    public Closure $create;
    public Closure $delete;
    public Closure $rename;
    public Closure $clear;
    public Closure $copy;

    public function getCacheDir(): string
    {
        return call_user_func($this->cache);
    }

    public function getPackagesDir(): string
    {
        return call_user_func($this->packages);
    }

    public function createDir(string $dir, int $permissions = 0755): void
    {
        call_user_func($this->create, $dir, $permissions);
    }

    public function delete(string $file): void
    {
        call_user_func($this->delete, $file);
    }

    public function rename(string $from, string $to): void
    {
        call_user_func($this->rename, $from, $to);
    }
    public function clear(string $dir, string $path): void
    {
        call_user_func($this->clear, $dir, $path);
    }

    public function copy(string $from, string $to): void
    {
        call_user_func($this->copy, $from, $to);
    }

    public function getTaskDir(): string
    {
        return call_user_func($this->task);
    }

    public function getStateDir(): string
    {
        return call_user_func($this->state);
    }

    public function getOtherDir(): string
    {
        return call_user_func($this->other);
    }

    public function getRootDir(): string
    {
        return call_user_func($this->root);
    }

    public function getHubDir(): string {return "";}
    public function getLogDir(): string {return "";}
}