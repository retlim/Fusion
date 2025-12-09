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

namespace Valvoid\Fusion\Tests\Tasks\Inflate\Mocks;

use Closure;
use Valvoid\Fusion\Dir\Proxy;

class DirectoryMock implements Proxy
{
    public Closure $cache;
    public Closure $packages;

    public function getCacheDir(): string
    {
        return call_user_func($this->cache);
    }

    public function getPackagesDir(): string
    {
        return call_user_func($this->packages);
    }

    public function createDir(string $dir, int $permissions = 0755): void {}
    public function delete(string $file): void {}
    public function rename(string $from, string $to): void {}
    public function clear(string $dir, string $path): void {}
    public function copy(string $from, string $to): void {}
    public function getTaskDir(): string {return "";}
    public function getStateDir(): string {return "";}
    public function getOtherDir(): string {return "";}
    public function getRootDir(): string {return "";}
    public function getHubDir(): string {return "";}
    public function getLogDir(): string {return "";}
}