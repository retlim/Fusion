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
 * Default current packager directory instance.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Instance implements Proxy
{
    /** @var Proxy Implementation. */
    protected Proxy $logic;

    /**
     * Constructs the directory.
     *
     *  @param Proxy|Logic $logic Any or default logic implementation.
     */
    public function __construct(Proxy|Logic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * Returns current (locked) task cache directory.
     *
     * @return string Directory.
     */
    public function getTaskDir(): string
    {
        return $this->logic->getTaskDir();
    }

    /**
     * Returns current (locked) task cache directory.
     *
     * @return string Directory.
     */
    public function getStateDir(): string
    {
        return $this->logic->getStateDir();
    }

    /**
     * Returns absolute cache directory.
     *
     * @return string Directory.
     */
    public function getCacheDir(): string
    {
        return $this->logic->getCacheDir();
    }

    /**
     * Returns other directory.
     *
     * @return string Directory.
     */
    public function getOtherDir(): string
    {
        return $this->logic->getOtherDir();
    }

    /**
     * Returns packages directory.
     *
     * @return string Directory.
     */
    public function getPackagesDir(): string
    {
        return $this->logic->getPackagesDir();
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
    public function getRootDir(): string
    {
        return $this->logic->getRootDir();
    }

    /**
     * Creates directory.
     *
     * @param string $dir Dir.
     * @param int $permissions Permissions.
     * @throws Error Internal error.
     */
    public function createDir(string $dir, int $permissions = 0755): void
    {
        $this->logic->createDir($dir, $permissions);
    }

    /**
     * Renames file or directory.
     *
     * @param string $from Current file or directory.
     * @param string $to To file or directory.
     * @throws Error Internal error.
     */
    public function rename(string $from, string $to): void
    {
        $this->logic->rename($from, $to);
    }

    /**
     * Copies file.
     *
     * @param string $from Current file.
     * @param string $to To file.
     * @throws Error Internal error.
     */
    public function copy(string $from, string $to): void
    {
        $this->logic->copy($from, $to);
    }

    /**
     * Deletes file or directory.
     *
     * @param string $file Dir or file.
     * @throws Error Internal error.
     */
    public function delete(string $file): void
    {
        $this->logic->delete($file);
    }

    /**
     * Deletes empty path parts.
     *
     * @param string $dir Directory.
     * @param string $path Path.
     * @throws Error Internal error.
     */
    public function clear(string $dir, string $path): void
    {
        $this->logic->clear($dir, $path);
    }
}