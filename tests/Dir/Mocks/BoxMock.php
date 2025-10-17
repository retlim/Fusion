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

namespace Valvoid\Fusion\Tests\Dir\Mocks;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Dir\Proxy;

class BoxMock extends Box
{
    public Proxy $dir;
    public function get(string $class, ...$args): object
    {
        return $this->dir ??= new class implements Proxy
        {
            public $calls = [];

            public function getTaskDir(): string
            {
                $this->calls[] = __FUNCTION__;
                return "";
            }

            public function getStateDir(): string
            {
                $this->calls[] = __FUNCTION__;
                return "";
            }

            public function getCacheDir(): string
            {
                $this->calls[] = __FUNCTION__;
                return "";
            }

            public function getOtherDir(): string
            {
                $this->calls[] = __FUNCTION__;
                return "";
            }

            public function getPackagesDir(): string
            {
                $this->calls[] = __FUNCTION__;
                return "";
            }

            public function getRootDir(): string
            {
                $this->calls[] = __FUNCTION__;
                return "";
            }

            public function createDir(string $dir, int $permissions): void
            {
                $this->calls[] = __FUNCTION__;
            }

            public function rename(string $from, string $to): void
            {
                $this->calls[] = __FUNCTION__;
            }

            public function copy(string $from, string $to): void
            {
                $this->calls[] = __FUNCTION__;
            }

            public function delete(string $file): void
            {
                $this->calls[] = __FUNCTION__;
            }

            public function clear(string $dir, string $path): void
            {
                $this->calls[] = __FUNCTION__;
            }

            public function getHubDir(): string
            {
                $this->calls[] = __FUNCTION__;
                return "";
            }

            public function getLogDir(): string
            {
                $this->calls[] = __FUNCTION__;
                return "";
            }
        };
    }
}