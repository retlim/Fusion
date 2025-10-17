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
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Valvoid\Fusion\Wrappers;

/**
 * Extension wrapper.
 */
class Extension
{
    /**
     * Find out whether an extension is loaded
     *
     * @param string $extension The extension name.
     * @return bool true if the extension identified
     * by name is loaded, false otherwise.
     */
    public function isLoaded(string $extension): bool
    {
        return extension_loaded($extension);
    }

    /**
     * Returns an array with the names of all modules compiled and loaded
     *
     * @param bool $zend_extensions [optional]
     * Only return Zend extensions, if not then regular extensions,
     * like mysqli are listed. Defaults to false (return regular extensions).
     * @return string[] an indexed array of all the modules names.
     */
    public function getLoaded(bool $zend_extensions = false): array
    {
        return get_loaded_extensions($zend_extensions);
    }
}