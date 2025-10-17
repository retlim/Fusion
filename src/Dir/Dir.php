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

use Exception;
use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Log\Events\Errors\Error;

/**
 * Root package directory roxy providing normalized filesystem operations.
 */
class Dir
{
    /**
     * Returns the directory used for temporary task data during
     * package operations.
     *
     * @return string Absolute path to the task directory.
     * @throws Error|Exception Internal error.
     */
    public static function getTaskDir(): string
    {
        return Box::getInstance()->get(Proxy::class)
            ->getTaskDir();
    }

    /**
     * Returns the directory used to store the new state.
     *
     * @return string Absolute path to the state directory.
     * @throws Error|Exception Internal error.
     */
    public static function getStateDir(): string
    {
        return Box::getInstance()->get(Proxy::class)
            ->getStateDir();
    }

    /**
     * Returns absolute cache directory.
     *
     * @return string Directory.
     * @throws Error|Exception Internal error.
     */
    public static function getCacheDir(): string
    {
        return Box::getInstance()->get(Proxy::class)
            ->getCacheDir();
    }

    /**
     * Returns other directory.
     *
     * @return string Directory.
     * @throws Error|Exception Internal error.
     */
    public static function getOtherDir(): string
    {
        return Box::getInstance()->get(Proxy::class)
            ->getOtherDir();
    }

    /**
     * Returns the storage directory where downloaded packages
     * are stored.
     *
     * @return string Absolute path to the hub directory.
     * @throws Exception
     */
    public static function getHubDir(): string
    {
        return Box::getInstance()->get(Proxy::class)
            ->getHubDir();
    }

    /**
     * Returns the storage directory where logs are stored.
     *
     * @return string Absolute path to the log directory.
     * @throws Exception
     */
    public static function getLogDir(): string
    {
        return Box::getInstance()->get(Proxy::class)
            ->getLogDir();
    }

    /**
     * Returns the storage directory where packages for the new state
     * are stored individually by their ID subdirectories.
     *
     * @return string Absolute path to the new state packages directory.
     * @throws Error|Exception Internal error.
     */
    public static function getPackagesDir(): string
    {
        return Box::getInstance()->get(Proxy::class)
            ->getPackagesDir();
    }

    /**
     * Returns root directory.
     *
     * @return string Root dir.
     * @throws Error|Exception Internal error.
     */
    public static function getRootDir(): string
    {
        return Box::getInstance()->get(Proxy::class)
            ->getRootDir();
    }

    /**
     * Creates directory.
     *
     * @param string $dir Dir.
     * @param int $permissions Permissions.
     * @throws Error Internal error.
     * @throws Exception
     */
    public static function createDir(string $dir, int $permissions = 0755): void
    {
        Box::getInstance()->get(Proxy::class)
            ->createDir($dir, $permissions);
    }

    /**
     * Renames file or directory.
     *
     * @param string $from Current file or directory.
     * @param string $to To file or directory.
     * @throws Error Internal error.
     * @throws Exception
     */
    public static function rename(string $from, string $to): void
    {
        Box::getInstance()->get(Proxy::class)
            ->rename($from, $to);
    }

    /**
     * Copies file.
     *
     * @param string $from Current file.
     * @param string $to To file.
     * @throws Error Internal error.
     * @throws Exception
     */
    public static function copy(string $from, string $to): void
    {
        Box::getInstance()->get(Proxy::class)
            ->copy($from, $to);
    }

    /**
     * Deletes file or directory.
     *
     * @param string $file Dir or file.
     * @throws Error Internal error.
     * @throws Exception
     */
    public static function delete(string $file): void
    {
        Box::getInstance()->get(Proxy::class)
            ->delete($file);
    }

    /**
     * Deletes empty path parts.
     *
     * @param string $dir Directory.
     * @param string $path Path.
     * @throws Error Internal error.
     * @throws Exception
     */
    public static function clear(string $dir, string $path): void
    {
        Box::getInstance()->get(Proxy::class)
            ->clear($dir, $path);
    }
}