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

namespace Valvoid\Fusion\Tests\Log\Mocks;

use Closure;
use ReflectionClass;
use Valvoid\Fusion\Bus\Events\Event;
use Valvoid\Fusion\Container\Container;
use Valvoid\Fusion\Container\Proxy\Proxy;
use Valvoid\Fusion\Log\Events\Interceptor;

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
            public \Valvoid\Fusion\Log\Proxy\Proxy $log;
            public function get(string $class, ...$args): object
            {
                return $this->log ??= new class implements \Valvoid\Fusion\Log\Proxy\Proxy
                {
                    public $calls = [];

                    public function addInterceptor(Interceptor $interceptor): void
                    {
                        $this->calls[] = __FUNCTION__;
                    }

                    public function removeInterceptor(): void
                    {
                        $this->calls[] = __FUNCTION__;
                    }

                    public function error(string|\Valvoid\Fusion\Log\Events\Event $event): void
                    {
                        $this->calls[] = __FUNCTION__;
                    }

                    public function warning(string|\Valvoid\Fusion\Log\Events\Event $event): void
                    {
                        $this->calls[] = __FUNCTION__;
                    }

                    public function notice(string|\Valvoid\Fusion\Log\Events\Event $event): void
                    {
                        $this->calls[] = __FUNCTION__;
                    }

                    public function info(string|\Valvoid\Fusion\Log\Events\Event $event): void
                    {
                        $this->calls[] = __FUNCTION__;
                    }

                    public function verbose(string|\Valvoid\Fusion\Log\Events\Event $event): void
                    {
                        $this->calls[] = __FUNCTION__;
                    }

                    public function debug(string|\Valvoid\Fusion\Log\Events\Event $event): void
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