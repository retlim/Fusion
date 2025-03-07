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

namespace Valvoid\Fusion\Tests\Tasks\Shift\Mocks;

use ReflectionClass;
use ReflectionException;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Cache;
use Valvoid\Fusion\Bus\Events\Root;
use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Dir\Proxy\Logic;

/**
 * Mocked dir.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class DirMock
{
    private Dir $dir;

    private ReflectionClass $reflection;

    /**
     * @throws ReflectionException
     */
    public function __construct()
    {
        $this->reflection = new ReflectionClass(Dir::class);
        $this->reflection->setStaticPropertyValue("instance", new class extends Dir
        {
            protected string $cache;

            public function __construct()
            {
                $this->proxy = new class extends Logic
                {
                    public function __construct()
                    {
                        $this->root = dirname(__DIR__) . "/cache";
                        $this->cache = dirname(__DIR__) . "/cache/cache";

                        Bus::addReceiver("whatever", $this->handleBusEvent(...),
                            Cache::class);
                    }

                };
            }

            /**
             * Handles bus event.
             *
             * @param Cache $event Event.
             */
            protected function handleBusEvent(Cache $event): void
            {
                $this->cache = $event->getDir();
            }
        });
    }

    public function destroy(): void
    {
        $this->reflection->setStaticPropertyValue("instance", null);
    }
}