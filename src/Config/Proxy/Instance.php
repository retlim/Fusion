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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Valvoid\Fusion\Config\Proxy;

use Valvoid\Fusion\Log\Events\Errors\Config as ConfigError;
use Valvoid\Fusion\Log\Events\Errors\Metadata;

/**
 * Default config instance.
 *
 * @Copyright Valvoid
 * @license GNU GPLv3
 */
class Instance implements Proxy
{
    /** @var Proxy Implementation. */
    protected Proxy $logic;

    /**
     * Constructs the config.
     *
     * @param string $root
     * @param array $lazy
     * @param array $config Runtime config layer.
     */
    public function __construct(string $root, array &$lazy, array $config)
    {
        $this->logic = new Logic($root, $lazy, $config);
    }

    /**
     * Builds the config.
     *
     * @throws ConfigError Invalid config exception.
     * @throws Metadata Invalid meta exception.
     */
    public function build(): void
    {
        $this->logic->build();
    }

    /**
     * Returns composite settings.
     *
     * @param string ...$breadcrumb Index path inside config.
     * @return mixed Config.
     */
    public function get(string ...$breadcrumb): mixed
    {
        return $this->logic->get(...$breadcrumb);
    }

    /**
     * Returns lazy code registry.
     *
     * @return array Lazy.
     */
    public function getLazy(): array
    {
        return $this->logic->getLazy();
    }

    /**
     * Returns indicator for existing lazy code.
     *
     * @param string $class Class.
     * @return bool Indicator.
     */
    public function hasLazy(string $class): bool
    {
        return $this->logic->hasLazy($class);
    }
}