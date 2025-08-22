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

namespace Valvoid\Fusion\Tests\Config\Mocks;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Proxy\Logic;
use Valvoid\Fusion\Config\Proxy\Proxy;

/**
 * Mocked container.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class BoxMock extends Box
{
    public $bus;
    public $config;

    public function get(string $class, ...$args): object
    {
        if ($class == Proxy::class)
            return $this->config ??= new class implements Proxy
            {
                public $calls = [];

                public function get(string ...$breadcrumb): mixed
                {
                    $this->calls[] = __FUNCTION__;
                    return 0;
                }
                public function getLazy(): array {
                    $this->calls[] = __FUNCTION__;
                    return [];
                }
                public function hasLazy(string $class): bool
                {$this->calls[] = __FUNCTION__;

                    return false;
                }
            };

        return $this->bus ??= new Logic;
    }
}