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

namespace Valvoid\Fusion\Tests\Hub\Mocks;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Hub\Logic;
use Valvoid\Fusion\Hub\Proxy as HubProxy;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class BoxMock extends Box
{
    public HubProxy $hub;
    public ConfigMock $config;
    public CacheMock $cache;

    public array $classes = [
        "Valvoid\Fusion\Hub\Cache" => CacheMock::class,
        "Valvoid\Fusion\Wrappers\File" => FileMock::class
    ];
    public function get(string $class, ...$args): object
    {
        return match ($class) {
            "Valvoid\Fusion\Hub\Proxy" => $this->hub ??= new ($this->classes[$class]),
            "Valvoid\Fusion\Hub\Cache" => $this->cache ??= new ($this->classes[$class]),
            "Valvoid\Fusion\Wrappers\File" => new ($this->classes[$class]),
            default => new $class(...$args)
        };
    }

    public function setUpStaticTests(): void
    {
        $this->classes["Valvoid\Fusion\Hub\Proxy"] = ProxyMock::class;
    }

    public function setUpLogicTests(): void
    {
        // reset
        unset($this->hub);

        $this->classes["Valvoid\Fusion\Hub\Proxy"] = Logic::class;
    }
}