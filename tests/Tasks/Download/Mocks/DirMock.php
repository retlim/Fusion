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

namespace Valvoid\Fusion\Tests\Tasks\Download\Mocks;

use ReflectionClass;
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
    private ReflectionClass $reflection;

    public function __construct()
    {
        $this->reflection = new ReflectionClass(Dir::class);
        $this->reflection->setStaticPropertyValue("instance", new class extends Dir
        {
            public function __construct()
            {
                $this->proxy = new class extends Logic
                {
                    public function __construct()
                    {
                        $this->root = __DIR__ . "/package";
                        $this->cache = __DIR__ . "/package/cache";
                    }
                };
            }
        });
    }

    public function destroy(): void
    {
        $this->reflection->setStaticPropertyValue("instance", null);
    }
}