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
 * Abstract standard file logic.
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
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

    /**
     * Checks whether a file or directory exists.
     *
     * @param string $file File.
     * @return bool True if the file exists, false otherwise.
     */
    public function exists(string $file): bool
    {
        return file_exists($file);
    }

    /**
     * Reads entire file into a string.
     *
     * @param string $file File.
     * @return string|false The function returns the read
     * data or false on failure.
     */
    public function get(string $file): string|false
    {
        return file_get_contents($file);
    }

    /**
     * Returns required file content.
     *
     * @param string $file File.
     * @return mixed Content.
     */
    public function require(string $file): mixed
    {
        return require $file;
    }

    /**
     * Returns included file content.
     *
     * @param string $file File.
     * @return mixed Content.
     */
    public function include(string $file): mixed
    {
        return include $file;
    }

    /**
     * Tells whether the filename is a regular file
     *
     * @param string $file Path to the file.
     * @return bool true if the filename exists and is a regular file,
     * false otherwise.
     */
    public function is(string $file): bool
    {
        return is_file($file);
    }

    /**
     * Deletes a file
     *
     * @param string $file Path to the file.
     * @return bool true on success or false on failure.
     */
    public function unlink(string $file): bool
    {
        return unlink($file);
    }

    /**
     * Copies file
     *
     * @param string $from Path to the source file.
     * @param string $to The destination path.
     * @return bool true on success or false on failure.
     */
    public function copy(string $from, string $to): bool
    {
        return copy($from, $to);
    }

    /**
     * Gets file modification time
     *
     * @param string $file Path to the file.
     * @return int|false the time the file was last modified,
     * or false on failure. The time is returned as a Unix
     * timestamp, which is suitable for the date function.
     */
    public function time(string $file): int|false
    {
        return filemtime($file);
    }
}