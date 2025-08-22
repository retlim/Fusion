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

namespace Valvoid\Fusion\Tests\Tasks\Snap\Mocks;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Proxy\Proxy;
use Valvoid\Fusion\Dir\Proxy\Logic;

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
    public function get(string $class, ...$args): object
    {
        if ($class === Proxy::class)
            return $this->bus;

        if ($class === \Valvoid\Fusion\Group\Proxy\Proxy::class)
            return $this->group;

        if ($class === \Valvoid\Fusion\Log\Proxy\Proxy::class)
            return $this->log;

        if ($class === \Valvoid\Fusion\Dir\Proxy\Proxy::class)
            return new class extends Logic
            {
                public function __construct()
                {
                    $this->root = __DIR__ . "/package";
                    $this->cache = __DIR__ . "/package/cache";
                }
            };

        return parent::get($class, ...$args);
    }
}