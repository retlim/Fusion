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

namespace Valvoid\Fusion\Hub\Requests\Remote\Wrappers;

/**
 * File wrapper.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class File
{
    /**
     * Writes data to file.
     *
     * @param string $file File.
     * @param mixed $data Data.
     * @return int|false The function returns the number of
     * bytes that were written to the file, or false on failure.
     */
    public function put(string $file, mixed $data): int|false
    {
        return file_put_contents($file, $data);
    }
}