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

use ReflectionClass;
use Valvoid\Fusion\Container\Container;
use Valvoid\Fusion\Container\Proxy\Proxy;

/**
 * Mocked container.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ContainerMock implements Proxy
{
    private ReflectionClass $reflection;
    public GroupMock $group;
    public DirMock $dir;
    public LogMock $log;

    public function __construct()
    {
        $this->reflection = new ReflectionClass(Container::class);
        $this->group = new GroupMock;
        $this->log = new LogMock;
        $this->dir = new DirMock;

        $this->reflection->setStaticPropertyValue("instance",
            new class($this) extends Container
            {
                public function __construct(protected Proxy $proxy) {}
            });
    }

    public function get(string $class, ...$args): object
    {
        return match ($class) {
            \Valvoid\Fusion\Group\Proxy\Proxy::class => $this->group,
            \Valvoid\Fusion\Dir\Proxy\Proxy::class => $this->dir,
            default => $this->log
        };
    }

    public function refer(string $id, string $class): void {}
    public function unset(string $class): void {}

    public function destroy(): void
    {
        $this->reflection->setStaticPropertyValue("instance", null);
    }
}