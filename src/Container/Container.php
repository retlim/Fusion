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

use Valvoid\Fusion\Container\Proxy\Instance;
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
    /** @var ?Container Runtime instance. */
    private static ?Container $instance = null;

    /** @var Proxy Decoupled logic. */
    protected Proxy $logic;

    /**
     * Constructs the container.
     *
     * @param Proxy|Instance $logic Any or default instance logic.
     */
    private function __construct(Proxy|Instance $logic)
    {
        // singleton
        self::$instance ??= $this;
        $this->logic = $logic;
    }

    /**
     * Destroys the bus instance.
     *
     * @return bool True for success.
     */
    public function destroy(): bool
    {
        self::$instance = null;

        return true;
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
        return self::$instance->logic->get($class, ...$args);
    }
}