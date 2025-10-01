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

namespace Valvoid\Fusion\Tests\Hub\Requests\Local\Offset\Mocks;

use Valvoid\Fusion\Dir\Proxy;

/**
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class DirMock implements Proxy
{
    public function getRootDir(): string
    {
        return "/root";
    }

    public function getTaskDir(): string {return "";}
    public function getStateDir(): string {return "";}
    public function getCacheDir(): string {return "";}
    public function getOtherDir(): string {return "";}
    public function getPackagesDir(): string {return "";}
    public function createDir(string $dir, int $permissions): void {}
    public function rename(string $from, string $to): void {}
    public function copy(string $from, string $to): void{}
    public function delete(string $file): void {}
    public function clear(string $dir, string $path): void {}
    public function getHubDir(): string {return "";}
    public function getLogDir(): string {return "";}
}