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

namespace Valvoid\Fusion\Tests\Tasks\Shift\Mocks;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Cache;
use Valvoid\Fusion\Bus\Proxy\Proxy;
use Valvoid\Fusion\Dir\Logic;
use Valvoid\Fusion\Wrappers\Dir;
use Valvoid\Fusion\Wrappers\File;

/**
 * Mocked container.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class BoxMock extends Box
{
    public BusMock $bus;
    public GroupMock $group;
    public LogMock $log;
    public Logic $dir;

    public function get(string $class, ...$args): object
    {
        if ($class === Proxy::class)
            return $this->bus;

        if ($class === \Valvoid\Fusion\Group\Group::class)
            return $this->group;

        if ($class === \Valvoid\Fusion\Log\Proxy::class)
            return $this->log;

        if ($class === \Valvoid\Fusion\Dir\Proxy::class)
            return $this->dir ??= new class extends Logic
            {
                public function __construct()
                {
                    $this->root = dirname(__DIR__) . "/cache";
                    $this->cache = dirname(__DIR__) . "/cache/cache";
                    $this->file = new File();
                    $this->dir = new Dir();

                    Bus::addReceiver("whatever", $this->handleBusEvent(...),
                        Cache::class);
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
            };

        return parent::get($class, ...$args);
    }
}