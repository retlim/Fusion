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

namespace Valvoid\Fusion\Tests\Config\Parser\Tasks\Mocks;

use Closure;
use ReflectionClass;
use Valvoid\Fusion\Bus\Events\Event;
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
            public function get(string $class, ...$args): object
            {
                return new class implements \Valvoid\Fusion\Config\Proxy\Proxy
                {
                    public function get(string ...$breadcrumb): mixed
                    {
                        return new class implements \Valvoid\Fusion\Config\Proxy\Proxy
                        {
                            // @phpstan-ignore-next-line
                            public function __construct(string $root = "", array &$lazy = [], array $config = []) {}
                            public function get(string ...$breadcrumb): mixed {return 0;}
                            public function getLazy(): array {return [];}
                            public function hasLazy(string $class): bool
                            {
                                // no custom parser
                                return false;
                            }
                        };
                    }

                    public function getLazy(): array
                    {
                        return [];
                    }

                    public function hasLazy(string $class): bool
                    {
                        return false;
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

    public function addParser(): void
    {
        $this->logic = new class implements Proxy
        {
            public function get(string $class, ...$args): object
            {
                return new class implements \Valvoid\Fusion\Config\Proxy\Proxy
                {
                    public function get(string ...$breadcrumb): mixed {return 0;}
                    public function getLazy(): array {return [];}
                    public function hasLazy(string $class): bool
                    {
                        // has custom parser
                        return true;
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