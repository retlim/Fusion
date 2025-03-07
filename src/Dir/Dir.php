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

use Valvoid\Fusion\Dir\Proxy\Logic;
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
    /** @var ?Dir Runtime instance. */
    private static ?Dir $instance = null;

    /** @var Proxy Decoupled logic. */
    protected Proxy $proxy;

    /**
     * Constructs the directory.
     *
     * @param Proxy|Logic $proxy Any or default logic.
     */
    private function __construct(Proxy|Logic $proxy)
    {
        // singleton
        self::$instance ??= $this;
        $this->proxy = $proxy;
    }

    /**
     * Returns current (locked) task cache directory.
     *
     * @return string Directory.
     */
    public static function getTaskDir(): string
    {
        return self::$instance->proxy->getTaskDir();
    }

    /**
     * Returns current (locked) task cache directory.
     *
     * @return string Directory.
     */
    public static function getStateDir(): string
    {
        return self::$instance->proxy->getStateDir();
    }

    /**
     * Returns absolute cache directory.
     *
     * @return string Directory.
     */
    public static function getCacheDir(): string
    {
        return self::$instance->proxy->getCacheDir();
    }

    /**
     * Returns other directory.
     *
     * @return string Directory.
     */
    public static function getOtherDir(): string
    {
        return self::$instance->proxy->getOtherDir();
    }

    /**
     * Returns packages directory.
     *
     * @return string Directory.
     */
    public static function getPackagesDir(): string
    {
        return self::$instance->proxy->getPackagesDir();
    }

    /**
     * Returns root directory.
     *
     * @return string Root dir.
     */
    public static function getRootDir(): string
    {
        return self::$instance->proxy->getRootDir();
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
        self::$instance->proxy->createDir($dir, $permissions);
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
        self::$instance->proxy->rename($from, $to);
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
        self::$instance->proxy->copy($from, $to);
    }

    /**
     * Deletes file or directory.
     *
     * @param string $file Dir or file.
     * @throws Error Internal error.
     */
    public static function delete(string $file): void
    {
        self::$instance->proxy->delete($file);
    }

    /**
     * Deletes empty path parts.
     *
     * @param string $dir Directory.
     * @param string $path Path.
     * @throws Error
     */
    public static function clear(string $dir, string $path): void
    {
        self::$instance->proxy->clear($dir, $path);
    }
}