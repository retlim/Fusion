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

namespace Valvoid\Fusion\Dir\Proxy;

use Valvoid\Fusion\Log\Events\Errors\Error;

/**
 * Current package directory.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
interface Proxy
{
    /**
     * Returns current (locked) task cache directory.
     *
     * @return string Directory.
     */
    public function getTaskDir(): string;

    /**
     * Returns current (locked) task cache directory.
     *
     * @return string Directory.
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
     * Returns packages directory.
     *
     * @return string Directory.
     */
    public function getPackagesDir(): string;

    /**
     * Normalizes working directory.
     *
     * @throws Error Internal error.
     */
    public function normalize(): void;

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