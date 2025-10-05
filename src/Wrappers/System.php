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

namespace Valvoid\Fusion\Wrappers;

/**
 * System wrapper.
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class System
{
    /**
     * Gets the value of an environment variable
     *
     * @param string $name The variable name.
     * @return array|string|false the value of the environment variable
     * varname or an associative array with all environment variables
     * if no variable name is provided, or false on an error.
     */
    public function getEnvVariable(string $name): array|string|false
    {
        return getenv($name);
    }

    /**
     * The operating system family PHP was built for.
     *
     * @return string Either of 'Windows', 'BSD', 'Darwin',
     * 'Solaris', 'Linux' or 'Unknown'.
     */
    public function getOsFamily(): string
    {
        return PHP_OS_FAMILY;
    }
}