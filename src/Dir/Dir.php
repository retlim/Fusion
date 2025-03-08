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

namespace Valvoid\Fusion\Dir;

use Valvoid\Fusion\Container\Container;
use Valvoid\Fusion\Dir\Proxy\Proxy;
use Valvoid\Fusion\Log\Events\Errors\Error;

/**
 * Static current package directory proxy.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Dir
{
    /**
     * Returns current (locked) task cache directory.
     *
     * @return string Directory.
     * @throws Error Internal error.
     */
    public static function getTaskDir(): string
    {
        return Container::get(Proxy::class)
            ->getTaskDir();
    }

    /**
     * Returns current (locked) task cache directory.
     *
     * @return string Directory.
     * @throws Error Internal error.
     */
    public static function getStateDir(): string
    {
        return Container::get(Proxy::class)
            ->getStateDir();
    }

    /**
     * Returns absolute cache directory.
     *
     * @return string Directory.
     * @throws Error Internal error.
     */
    public static function getCacheDir(): string
    {
        return Container::get(Proxy::class)
            ->getCacheDir();
    }

    /**
     * Returns other directory.
     *
     * @return string Directory.
     * @throws Error Internal error.
     */
    public static function getOtherDir(): string
    {
        return Container::get(Proxy::class)
            ->getOtherDir();
    }

    /**
     * Returns packages directory.
     *
     * @return string Directory.
     * @throws Error Internal error.
     */
    public static function getPackagesDir(): string
    {
        return Container::get(Proxy::class)
            ->getPackagesDir();
    }

    /**
     * Returns root directory.
     *
     * @return string Root dir.
     * @throws Error Internal error.
     */
    public static function getRootDir(): string
    {
        return Container::get(Proxy::class)
            ->getRootDir();
    }

    /**
     * Creates directory.
     *
     * @param string $dir Dir.
     * @param int $permissions Permissions.
     * @throws Error Internal error.
     */
    public static function createDir(string $dir, int $permissions = 0755): void
    {
        Container::get(Proxy::class)
            ->createDir($dir, $permissions);
    }

    /**
     * Renames file or directory.
     *
     * @param string $from Current file or directory.
     * @param string $to To file or directory.
     * @throws Error Internal error.
     */
    public static function rename(string $from, string $to): void
    {
        Container::get(Proxy::class)
            ->rename($from, $to);
    }

    /**
     * Copies file.
     *
     * @param string $from Current file.
     * @param string $to To file.
     * @throws Error Internal error.
     */
    public static function copy(string $from, string $to): void
    {
        Container::get(Proxy::class)
            ->copy($from, $to);
    }

    /**
     * Deletes file or directory.
     *
     * @param string $file Dir or file.
     * @throws Error Internal error.
     */
    public static function delete(string $file): void
    {
        Container::get(Proxy::class)
            ->delete($file);
    }

    /**
     * Deletes empty path parts.
     *
     * @param string $dir Directory.
     * @param string $path Path.
     * @throws Error Internal error.
     */
    public static function clear(string $dir, string $path): void
    {
        Container::get(Proxy::class)
            ->clear($dir, $path);
    }
}