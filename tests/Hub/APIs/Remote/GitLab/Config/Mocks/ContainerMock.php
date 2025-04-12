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

namespace Valvoid\Fusion\Tests\Hub\APIs\Remote\GitLab\Config\Mocks;

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
        $this->logic = new class implements Proxy {

            public $bus;

            public function __construct()
            {
                $this->bus = new class implements \Valvoid\Fusion\Bus\Proxy\Proxy
                {
                    // last event
                    public ?Event $event = null;

                    public function broadcast(Event $event): void
                    {
                        $this->event = $event;
                    }

                    public function addReceiver(string $id, Closure $callback, string ...$events): void {}
                    public function removeReceiver(string $id, string ...$events): void {}
                };
            }

            public function get(string $class, ...$args): object
            {
                return $this->bus;
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