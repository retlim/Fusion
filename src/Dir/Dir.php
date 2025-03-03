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

use Valvoid\Fusion\Dir\Proxy\Instance;
use Valvoid\Fusion\Dir\Proxy\Proxy;
use Valvoid\Fusion\Log\Events\Errors\Error;

/**
 * Current package directory proxy.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Dir
{
    /** @var ?Dir Runtime instance. */
    private static ?Dir $instance = null;

    /** @var Proxy Decoupled logic. */
    protected Proxy $logic;

    /**
     * Constructs the directory.
     *
     * @param Proxy|Instance $logic Any or default instance logic.
     */
    private function __construct(Proxy|Instance $logic)
    {
        // singleton
        self::$instance ??= $this;
        $this->logic = $logic;
    }

    /**
     * Destroys the cache instance.
     *
     * @return bool True for success.
     */
    public function destroy(): bool
    {
        self::$instance = null;

        return true;
    }

    /**
     * Returns current (locked) task cache directory.
     *
     * @return string Directory.
     */
    public static function getTaskDir(): string
    {
        return self::$instance->logic->getTaskDir();
    }

    /**
     * Returns current (locked) task cache directory.
     *
     * @return string Directory.
     */
    public static function getStateDir(): string
    {
        return self::$instance->logic->getStateDir();
    }

    /**
     * Returns absolute cache directory.
     *
     * @return string Directory.
     */
    public static function getCacheDir(): string
    {
        return self::$instance->logic->getCacheDir();
    }

    /**
     * Returns other directory.
     *
     * @return string Directory.
     */
    public static function getOtherDir(): string
    {
        return self::$instance->logic->getOtherDir();
    }

    /**
     * Returns packages directory.
     *
     * @return string Directory.
     */
    public static function getPackagesDir(): string
    {
        return self::$instance->logic->getPackagesDir();
    }

    /**
     * Normalizes working directory.
     *
     * @throws Error Internal error.
     */
    public function normalize(): void
    {
        $this->logic->normalize();
    }

    /**
     * Returns root directory.
     *
     * @return string Root dir.
     */
    public static function getRootDir(): string
    {
        return self::$instance->logic->getRootDir();
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
        self::$instance->logic->createDir($dir, $permissions);
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
        self::$instance->logic->rename($from, $to);
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
        self::$instance->logic->copy($from, $to);
    }

    /**
     * Deletes file or directory.
     *
     * @param string $file Dir or file.
     * @throws Error Internal error.
     */
    public static function delete(string $file): void
    {
        self::$instance->logic->delete($file);
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
        self::$instance->logic->clear($dir, $path);
    }
}