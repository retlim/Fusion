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

namespace Valvoid\Fusion\Dir;

use Valvoid\Fusion\Log\Events\Errors\Error;

/**
 * Root package directory providing normalized filesystem operations
 * and locations.
 */
interface Proxy
{
    /**
     * Returns the directory used for temporary task data during
     * package operations.
     *
     * @return string Absolute path to the task directory.
     */
    public function getTaskDir(): string;

    /**
     * Returns the directory used to store the new state.
     *
     * @return string Absolute path to the state directory.
     */
    public function getStateDir(): string;

    /**
     * Returns absolute cache directory.
     *
     * @return string Directory.
     */
    public function getCacheDir(): string;

    /**
     * Returns other directory.
     *
     * @return string Directory.
     */
    public function getOtherDir(): string;

    /**
     * Returns the storage directory where packages for the new state
     * are stored individually by their ID subdirectories.
     *
     * @return string Absolute path to the new state packages directory.
     */
    public function getPackagesDir(): string;

    /**
     * Returns the storage directory where downloaded packages
     * are stored.
     *
     * @return string Absolute path to the hub directory.
     */
    public function getHubDir(): string;

    /**
     * Returns the storage directory where logs are stored.
     *
     * @return string Absolute path to the log directory.
     */
    public function getLogDir(): string;

    /**
     * Returns root directory.
     *
     * @return string Root dir.
     */
    public function getRootDir(): string;

    /**
     * Creates directory.
     *
     * @param string $dir Dir.
     * @param int $permissions Permissions.
     * @throws Error Internal error.
     */
    public function createDir(string $dir, int $permissions): void;

    /**
     * Renames file or directory.
     *
     * @param string $from Current file or directory.
     * @param string $to To file or directory.
     * @throws Error Internal error.
     */
    public function rename(string $from, string $to): void;

    /**
     * Copies file.
     *
     * @param string $from Current file.
     * @param string $to To file.
     * @throws Error Internal error.
     */
    public function copy(string $from, string $to): void;

    /**
     * Deletes file or directory.
     *
     * @param string $file Dir or file.
     * @throws Error Internal error.
     */
    public function delete(string $file): void;

    /**
     * Deletes empty path parts.
     *
     * @param string $dir Directory.
     * @param string $path Path.
     * @throws Error Internal error.
     */
    public function clear(string $dir, string $path): void;
}