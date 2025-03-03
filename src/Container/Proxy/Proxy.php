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

namespace Valvoid\Fusion\Container\Proxy;

use Valvoid\Fusion\Log\Events\Errors\Error;

/**
 * Dependency container.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
interface Proxy
{
    /**
     * Returns an instantiated dependency.
     *
     * @template T
     * @param class-string<T> $class Class name.
     * @param mixed $args Static arguments.
     * @return T Instantiated dependency.
     * @throws Error Internal error.
     */
    public function get(string $class, mixed ...$args): object;
}