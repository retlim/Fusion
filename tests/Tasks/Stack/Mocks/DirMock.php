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

namespace Valvoid\Fusion\Tests\Tasks\Stack\Mocks;

use Valvoid\Fusion\Dir\Proxy;

/**
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class DirMock implements Proxy
{
    public string $stateDir = "state";
    public string $packagesDir = "packages";
    // state
    public array $structure = [];

    public array $errors = [];

    public function getStateDir(): string
    {
        return $this->stateDir;
    }

    public function getPackagesDir(): string
    {
        return $this->packagesDir;
    }

    public function createDir(string $dir, int $permissions): void
    {
        $this->structure[$dir] = [];
    }

    public function rename(string $from, string $to): void
    {
        $this->structure[$to] = [
            "from" => $from,
            "to" => $to
        ];
    }

    public function getCacheDir(): string { return ""; }
    public function getOtherDir(): string { return ""; }
    public function getRootDir(): string { return ""; }
    public function getTaskDir(): string { return ""; }
    public function copy(string $from, string $to): void {}
    public function delete(string $file): void {}
    public function clear(string $dir, string $path): void {}
}