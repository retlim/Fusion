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
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Valvoid\Fusion\Tests\Tasks\Image\Mocks;

use Closure;
use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Proxy;
use Valvoid\Fusion\Group\Group;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Metadata\Internal\Builder;

class BoxMock extends Box
{
    public BusMock $bus;
    public GroupMock $group;
    public ConfigMock $config;
    public Closure $builder;

    public function get(string $class, ...$args): object
    {
        if ($class === Proxy::class)
            return $this->bus;

        if ($class === Content::class)
            return new ContentMock;

        if ($class === Group::class)
            return $this->group;

        if ($class === \Valvoid\Fusion\Config\Proxy::class)
            return $this->config;

        if ($class === Builder::class)
            return call_user_func($this->builder, $args);

        return parent::get($class, ...$args);
    }
}