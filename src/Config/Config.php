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

use Valvoid\Fusion\Config\Proxy\Instance;
use Valvoid\Fusion\Config\Proxy\Proxy;
use Valvoid\Fusion\Log\Events\Errors\Config as ConfigError;
use Valvoid\Fusion\Log\Events\Errors\Metadata;

/**
 * Package manager configuration proxy.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Config
{
    /** @var ?Config Active instance. */
    private static ?Config $instance = null;

    /** @var Proxy Decoupled logic. */
    protected Proxy $logic;

    /**
     * Constructs the config.
     *
     * @param Proxy|Instance $logic Any or default instance logic..
     * @throws ConfigError Invalid config exception.
     * @throws Metadata Invalid meta exception.
     */
    private function __construct(Proxy|Instance $logic)
    {
        self::$instance ??= $this;
        $this->logic = $logic;

        // lazy boot due to self reference
        $this->logic->build();
    }

    /**
     * Destroys the cache instance.
     *
     * @return bool True for success.
     */
    public function destroy(): bool
    {
        self::$instance = null;

        return true;
    }

    /**
     * Returns composite settings.
     *
     * @param string ...$breadcrumb Index path inside config.
     * @return mixed Config.
     */
    public static function get(string ...$breadcrumb): mixed
    {
        return self::$instance->logic->get(...$breadcrumb);
    }

    /**
     * Returns lazy code registry.
     *
     * @return array Lazy.
     */
    public static function getLazy(): array
    {
        return self::$instance->logic->getLazy();
    }

    /**
     * Returns indicator for existing lazy code.
     *
     * @param string $class Class.
     * @return bool Indicator.
     */
    public static function hasLazy(string $class): bool
    {
        return self::$instance->logic->hasLazy($class);
    }
}
