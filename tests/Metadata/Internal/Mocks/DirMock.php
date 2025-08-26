<?php
/**
 * Fusion. A package manager for PHP-based projects.
 * Copyright Valvoid
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

namespace Valvoid\Fusion\Tests\Metadata\Internal\Mocks;

use Valvoid\Fusion\Dir\Proxy;

/**
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class DirMock implements Proxy
{
    public function getStateDir(): string
    {
        return __DIR__;
    }

    public function getTaskDir(): string {return "";}
    public function getCacheDir(): string {return "";}
    public function getOtherDir(): string {return "";}
    public function getPackagesDir(): string
    {
        return "";
    }
    public function getRootDir(): string {return "";}
    public function createDir(string $dir, int $permissions): void {}
    public function rename(string $from, string $to): void {}
    public function copy(string $from, string $to): void {}
    public function delete(string $file): void {}
    public function clear(string $dir, string $path): void {}
}