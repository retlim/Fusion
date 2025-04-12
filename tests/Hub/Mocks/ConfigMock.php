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

namespace Valvoid\Fusion\Tests\Hub\Mocks;

use Valvoid\Fusion\Config\Proxy\Proxy;

/**
 * Mocked config proxy.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ConfigMock implements Proxy
{
    public function get(string ...$breadcrumb): mixed
    {
        if ($breadcrumb[0] == "hub")
            return [
                "apis" => []
            ];

        // dir
        return "path";
    }

    public function getLazy(): array
    {
        return [];
    }

    public function hasLazy(string $class): bool
    {
        return false;
    }
}