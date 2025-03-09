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

namespace Valvoid\Fusion\Config;

use Valvoid\Fusion\Config\Proxy\Proxy;
use Valvoid\Fusion\Container\Container;
use Valvoid\Fusion\Log\Events\Errors\Error;

/**
 * Static config proxy.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Config
{
    /**
     * Returns composite settings.
     *
     * @param string ...$breadcrumb Index path inside config.
     * @return mixed Config.
     * @throws Error Internal error.
     */
    public static function get(string ...$breadcrumb): mixed
    {
        return Container::get(Proxy::class)
            ->get(...$breadcrumb);
    }

    /**
     * Returns lazy code registry.
     *
     * @return array Lazy.
     * @throws Error Internal error.
     */
    public static function getLazy(): array
    {
        return Container::get(Proxy::class)
            ->getLazy();
    }

    /**
     * Returns indicator for existing lazy code.
     *
     * @param string $class Class.
     * @return bool Indicator.
     * @throws Error Internal error.
     */
    public static function hasLazy(string $class): bool
    {
        return Container::get(Proxy::class)
            ->hasLazy($class);
    }
}
