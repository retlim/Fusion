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

use Valvoid\Fusion\Config\Proxy\Logic;
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
    protected Proxy $proxy;

    /**
     * Constructs the config.
     *
     * @param Proxy|Logic $proxy Any or default logic.
     * @throws ConfigError Invalid config exception.
     * @throws Metadata Invalid meta exception.
     */
    private function __construct(Proxy|Logic $proxy)
    {
        self::$instance ??= $this;
        $this->proxy = $proxy;

        // lazy boot due to self reference
        $this->proxy->build();
    }

    /**
     * Returns composite settings.
     *
     * @param string ...$breadcrumb Index path inside config.
     * @return mixed Config.
     */
    public static function get(string ...$breadcrumb): mixed
    {
        return self::$instance->proxy->get(...$breadcrumb);
    }

    /**
     * Returns lazy code registry.
     *
     * @return array Lazy.
     */
    public static function getLazy(): array
    {
        return self::$instance->proxy->getLazy();
    }

    /**
     * Returns indicator for existing lazy code.
     *
     * @param string $class Class.
     * @return bool Indicator.
     */
    public static function hasLazy(string $class): bool
    {
        return self::$instance->proxy->hasLazy($class);
    }
}
