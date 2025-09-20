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
 * Dir abstraction to improve testability.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Dir
{
    /**
     * List files and directories inside the specified path.
     *
     * @param string $dir The directory that will be scanned.
     * @param int $order By default, the sorted order is alphabetical in ascending
     * order. If the optional sorting_order is set to non-zero, then the
     * sort order is alphabetical in descending order.
     * @return array|false an array of filenames on success, or false on
     * failure. If directory is not a directory, then boolean false is
     * returned, and an error of level E_WARNING is generated.
     */
    public function getFilenames(string $dir, int $order = SCANDIR_SORT_ASCENDING): array|false
    {
        return scandir($dir, $order);
    }

    /**
     * Creates directory.
     *
     * @param string $dir The directory to create.
     * @return bool true on success or false on failure.
     */
    public function create(string $dir, int $permissions = 0755, bool $recursive = true): bool
    {
        return mkdir($dir, $permissions, $recursive);
    }

    /**
     * Tells whether the filename is a directory
     *
     * @param string $dir Path to the file.
     * @return bool true if the filename exists and is a directory,
     * false otherwise.
     */
    public function is(string $dir): bool
    {
        return is_dir($dir);
    }

    /**
     * Removes directory
     *
     * @param string $dir Path to the directory.
     * @return bool true on success or false on failure.
     */
    public function delete(string $dir): bool
    {
        return rmdir($dir);
    }

    /**
     * Renames a file or directory
     *
     * @param string $from Old name.
     * @param string $to New name.
     * @return bool true on success or false on failure.
     */
    public function rename(string $from, string $to): bool
    {
        return rename($from, $to);
    }
}
