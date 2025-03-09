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

namespace Valvoid\Fusion\Tests\Dir\Mocks;

use ReflectionClass;
use Valvoid\Fusion\Container\Container;
use Valvoid\Fusion\Container\Proxy\Proxy;

/**
 * Mocked container.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ContainerMock
{
    private ReflectionClass $reflection;
    public Proxy $logic;
    public function __construct()
    {
        $this->reflection = new ReflectionClass(Container::class);
        $this->logic = new class implements Proxy
        {
            public \Valvoid\Fusion\Dir\Proxy\Proxy $dir;
            public function get(string $class, ...$args): object
            {
                return $this->dir ??= new class implements \Valvoid\Fusion\Dir\Proxy\Proxy
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
                };
            }

            public function refer(string $id, string $class): void {}
            public function unset(string $class): void {}
        };

        $this->reflection->setStaticPropertyValue("instance", new class($this->logic) extends Container
        {
            public function __construct(protected Proxy $proxy) {}
        });
    }

    public function destroy(): void
    {
        $this->reflection->setStaticPropertyValue("instance", null);
    }
}