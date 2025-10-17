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

namespace Valvoid\Fusion\Tests\Mocks;

use Closure;
use Valvoid\Fusion\Dir\Proxy;

class DirectoryMock implements Proxy
{
    public Closure $state;
    public Closure $packages;
    public Closure $task;
    public Closure $other;
    public Closure $delete;

    public function getPackagesDir(): string
    {
        return call_user_func($this->packages);
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

    public function delete(string $file): void
    {
        call_user_func($this->delete, $file);
    }

    public function getCacheDir(): string {return "";}

    public function createDir(string $dir, int $permissions = 0755): void{}
    public function rename(string $from, string $to): void {}
    public function clear(string $dir, string $path): void {}
    public function copy(string $from, string $to): void {}
    public function getRootDir(): string {return "";}
    public function getHubDir(): string {return "";}
    public function getLogDir(): string {return "";}
}