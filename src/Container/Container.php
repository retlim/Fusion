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

namespace Valvoid\Fusion\Container;

use Valvoid\Fusion\Container\Proxy\Logic;
use Valvoid\Fusion\Container\Proxy\Proxy;
use Valvoid\Fusion\Log\Events\Errors\Error;

/**
 * Static dependency container proxy.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Container
{
    /** @var ?Container Sharable instance. */
    private static ?Container $instance = null;

    /** @var Proxy Decoupled logic. */
    protected Proxy $proxy;

    /**
     * Constructs the container.
     *
     * @param Proxy|Logic $proxy Any or default logic.
     */
    private function __construct(Proxy|Logic $proxy)
    {
        // sharable
        self::$instance ??= $this;
        $this->proxy = $proxy;
    }

    /**
     * Returns an instantiated dependency.
     *
     * @template T
     * @param class-string<T> $class Class name.
     * @param mixed $args Static arguments.
     * @return T Instantiated dependency.
     * @throws Error Internal error.
     */
    public static function get(string $class, mixed ...$args): object
    {
        return self::$instance->proxy->get($class, ...$args);
    }

    /**
     * Creates a sharable instance reference.
     *
     * @param string $id Identifier.
     * @param string $class Implementation.
     */
    public static function refer(string $id, string $class): void
    {
        self::$instance->proxy->refer($id, $class);
    }

    /**
     * Unsets static properties by setting default values.
     *
     * @param string $class Class name.
     * @throws Error Internal error.
     */
    public static function unset(string $class): void
    {
        self::$instance->proxy->unset($class);
    }
}