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

namespace Valvoid\Fusion\Tests\Tasks\Replicate\Mocks;

use ReflectionClass;
use ReflectionException;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Dir\Dir;

/**
 * Mocked bus.
 *
 * @Copyright Valvoid
 * @license GNU GPLv3
 */
class BusMock
{
    private Bus $bus;

    private ReflectionClass $reflection;

    /**
     * @throws ReflectionException
     */
    public function __construct()
    {
        $this->reflection = new ReflectionClass(Bus::class);
        $this->bus = $this->reflection->newInstanceWithoutConstructor();
        $this->reflection->setStaticPropertyValue("instance", $this->bus);
    }

    public function destroy(): void
    {
        $this->reflection->setStaticPropertyValue("instance", null);
    }
}