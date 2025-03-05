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

namespace Valvoid\Fusion\Tests\Tasks\Image\Mocks;

use Closure;
use ReflectionClass;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Event;
use Valvoid\Fusion\Bus\Proxy\Proxy;

/**
 * Mocked bus.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class BusMock
{
    private ReflectionClass $reflection;

    /**
     */
    public function __construct()
    {
        $this->reflection = new ReflectionClass(Bus::class);
        $this->reflection->setStaticPropertyValue("instance", new class extends Bus {
            public function __construct()
            {
                $this->logic = new class implements Proxy {

                    public function addReceiver(string $id, Closure $callback, string ...$events): void{}

                    public function broadcast(Event $event): void{}

                    public function removeReceiver(string $id, string ...$events): void{}
                };
            }
        });
    }

    public function destroy(): void
    {
        $this->reflection->setStaticPropertyValue("instance", null);
    }
}