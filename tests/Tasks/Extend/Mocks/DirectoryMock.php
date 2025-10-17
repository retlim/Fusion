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

namespace Valvoid\Fusion\Tests\Tasks\Extend\Mocks;

use Closure;
use Valvoid\Fusion\Dir\Proxy;

class DirectoryMock implements Proxy
{
    public Closure $packages;
    public Closure $create;
    public Closure $delete;
    public Closure $rename;
    public Closure $clear;

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

    public function copy(string $from, string $to): void {}
    public function getTaskDir(): string {return "";}
    public function getStateDir(): string {return "";}
    public function getCacheDir(): string {return "";}
    public function getOtherDir(): string {return "";}
    public function getRootDir(): string {return "";}
    public function getHubDir(): string {return "";}
    public function getLogDir(): string {return "";}
}