<?php
/**
 * Fusion - PHP Package Manager
 * Copyright © Valvoid
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

/**
 * Config.
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
interface Proxy
{
    /**
     * Returns composite settings.
     *
     * @param string ...$breadcrumb Index path inside config.
     * @return mixed Config.
     */
    public function get(string ...$breadcrumb): mixed;

    /**
     * Returns lazy code registry.
     *
     * @return array Lazy.
     */
    public function getLazy(): array;

    /**
     * Returns indicator for existing lazy code.
     *
     * @param string $class Class.
     * @return bool Indicator.
     */
    public function hasLazy(string $class): bool;
}