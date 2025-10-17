<?php
/*
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

use Valvoid\Fusion\Bus\Events\Cache;
use Valvoid\Fusion\Bus\Proxy as BusProxy;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Wrappers\Dir;
use Valvoid\Fusion\Wrappers\File;

/**
 * Root package directory providing normalized filesystem operations
 * and locations.
 */
class Logic implements Proxy
{
    /** @var string Root dir of the package. */
    private string $root;

    /**
     * @var string Mutable package cache dir. May change via bus
     * event during migration if the new package version defines a
     * different cache path in its metadata structure.
     */
    private string $state;

    /**
     * @var string OS-level cache directory for immutable artifacts
     * like downloaded packages.
     */
    private string $osCache;

    /**
     * @var string OS-level state directory for mutable runtime data
     * like logs or build flow directories.
     */
    private string $osState;

    /**
     * Constructs the directory.
     *
     * @param Dir $dir Wrapper for standard directory operations.
     * @param File $file Wrapper for standard file operations.
     * @param BusProxy $bus Event bus.
     * @param array $config Config.
     * @throws Error Internal error exception.
     */
    public function __construct(
        private readonly Dir $dir,
        private readonly File $file,
        BusProxy $bus,
        array $config)
    {
        $this->osState = $config["state"]["path"];
        $this->osCache = $config["cache"]["path"];
        $this->root = $config["dir"]["path"];

        $bus->addReceiver(static::class, $this->handleBusEvent(...),

            // new cache dir event
            Cache::class);

        // replace existing content with placeholder or
        // recycle package / empty dir content
        if ($dir->is($this->root))
            $config["dir"]["clearable"] ?
                $this->replaceContent() :
                $this->recycleContent();

        // create new placeholder package
        elseif ($config["dir"]["creatable"])
            $this->createContent();

        else throw new Error(
            "Can't create the specified directory '$this->root' " .
            "because 'creatable' is not set to true."
        );
    }

    /**
     * Recycles existing package or just empty dir content.
     *
     * @throws Error Internal error exception.
     */
    private function recycleContent(): void
    {
        // production metadata
        $file = "$this->root/fusion.json";

        if (!$this->file->exists($file)) {
            $filenames = $this->dir->getFilenames($this->root);

            if ($filenames === false)
                throw new Error(
                    "Can't scan directory '$this->root'. " .
                    "Check permissions."
                );

            // 2 = symbolic ., ..
            if (count($filenames) > 2)
                throw new Error(
                    "Unexpected content in directory " .
                    "'$this->root'. Set 'clearable' to true to allow " .
                    "automatic cleanup."
                );

            // empty existing dir
            // just add placeholder (default path)
            $this->copy(__DIR__ . "/placeholder.json", $file);

            // default placeholder
            $this->state = "$this->root/state";

            return;
        }

        $metadata = $this->file->get($file);

        if ($metadata === false)
            throw new Error(
                "Can't read metadata file '$file'. Check file " .
                "access and permissions."
            );

        $metadata = json_decode($metadata, true);

        if ($metadata === null)
            throw new Error(
                "Can't decode metadata file '$file'. JSON error: " .
                json_last_error_msg()
            );

        if (isset($metadata["structure"]) &&
            is_array($metadata["structure"])) {
            $path = $this->getStatePath($metadata["structure"]) ??
                $this->getCachePath($metadata["structure"]);

        } else $path = null;

        if ($path === null)
            throw new Error(
                "Can't determine cache path from '$file'. The " .
                "directory is not empty and automatic deletion is not " .
                "authorized. Set 'clearable' to true to allow deletion "  .
                "of invalid content."
            );

        $this->state = $this->root . $path;
    }

    /**
     * Create new placeholder package content.
     *
     * @throws Error Internal error exception.
     */
    private function createContent(): void
    {
        if (!$this->dir->create($this->root))
            throw new Error(
                "Can't create directory '$this->root' " .
                "(value of 'path'). Check parent directory permissions."
            );

        $this->copy(__DIR__ . "/placeholder.json",

            // rename to production metadata
            "$this->root/fusion.json");

        // default placeholder
        $this->state = "$this->root/state";
    }

    /**
     * Replaces existing content with placeholder package.
     *
     * @throws Error Internal error exception.
     */
    private function replaceContent(): void
    {
        $filenames = $this->dir->getFilenames($this->root);

        if ($filenames === false)
            throw new Error(
                "Can't scan directory '$this->root'. Check " .
                "permissions."
            );

        foreach ($filenames as $filename)
            if ($filename != "." && $filename != "..")
                $this->delete("$this->root/$filename");

        $this->copy(__DIR__ . "/placeholder.json",

            // rename to production metadata
            "$this->root/fusion.json");

        // default placeholder
        $this->state = "$this->root/state";
    }

    /**
     * Returns the cache path defined in the given structure.
     *
     * @param array $struct Package structure.
     * @param string $breadcrumb Internal path prefix.
     * @return string|null Cache path, or null if not found.
     */
    protected function getStatePath(array $struct, string $breadcrumb = ""): ?string
    {
        // assoc or seq key due to loadable inside cache folder
        foreach ($struct as $key => $value)
            if ($value == "stateful")
                return is_string($key) ?
                    $breadcrumb . $key :
                    $breadcrumb;

            elseif (is_array($value))
                if ($dir = $this->getStatePath($value, is_string($key) ?
                    $breadcrumb . $key :
                    $breadcrumb))
                    return $dir;

        return null;
    }

    /**
     * Returns the cache path defined in the given structure.
     *
     * @param array $struct Package structure.
     * @param string $breadcrumb Internal path prefix.
     * @return string|null Cache path, or null if not found.
     * @deprecated
     */
    protected function getCachePath(array $struct, string $breadcrumb = ""): ?string
    {
        // assoc or seq key due to loadable inside cache folder
        foreach ($struct as $key => $value)
            if ($value == "cache")
                return is_string($key) ?
                    $breadcrumb . $key :
                    $breadcrumb;

            elseif (is_array($value))
                if ($dir = $this->getCachePath($value, is_string($key) ?
                    $breadcrumb . $key :
                    $breadcrumb))
                    return $dir;

        return null;
    }

    /**
     * Returns the directory used for temporary task data during
     * package operations.
     *
     * @return string Absolute path to the task directory.
     */
    public function getTaskDir(): string
    {
        return "$this->osState/task";
    }

    /**
     * Returns the storage directory where downloaded packages
     * are stored.
     *
     * @return string Absolute path to the hub directory.
     */
    public function getHubDir(): string
    {
        return "$this->osCache/hub";
    }

    /**
     * Returns the storage directory where logs are stored.
     *
     * @return string Absolute path to the log directory.
     */
    public function getLogDir(): string
    {
        return "$this->osState/log";
    }

    /**
     * Returns the directory used to store the new state.
     *
     * @return string Absolute path to the state directory.
     */
    public function getStateDir(): string
    {
        return "$this->osState/state";
    }

    /**
     * Returns absolute cache directory.
     *
     * @return string Directory.
     */
    public function getCacheDir(): string
    {
        return $this->state;
    }

    /**
     * Returns other directory.
     *
     * @return string Directory.
     */
    public function getOtherDir(): string
    {
        return "$this->osState/other";
    }

    /**
     * Returns the storage directory where packages for the new state
     * are stored individually by their ID subdirectories.
     *
     * @return string Absolute path to the new state packages directory.
     */
    public function getPackagesDir(): string
    {
        return "$this->osState/packages";
    }

    /**
     * Returns root directory.
     *
     * @return string Root dir.
     */
    public function getRootDir(): string
    {
        return $this->root;
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
        if (!$this->file->exists($dir) &&
            !$this->dir->create($dir, $permissions))
            throw new Error(
                "Can't create directory '$dir'."
            );
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
        // normalize to parent directory
        // not all php os builds can't handle replacement
        if ($this->file->is($to)) {
            if (!$this->file->unlink($to))
                throw new Error(
                    "Can't rename the file '$from' to '$to' " .
                    "because the target file cannot be normalized to the "  .
                    "parent directory '" . dirname($to) . "'."
                );

        } elseif ($this->dir->is($to))
            if (!$this->dir->delete($to))
                throw new Error(
                    "Can't rename the directory '$from' to '$to' " .
                    "because the target directory cannot be normalized to the "  .
                    "parent directory '" . dirname($to) . "'."
                );

        if (!$this->dir->rename($from, $to))
            throw new Error(
                "Can't rename the file '$from' to '$to'."
            );
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
        if (!$this->file->copy($from, $to))
            throw new Error(
                "Can't copy the file '$from' to '$to'."
            );
    }

    /**
     * Deletes file or directory.
     *
     * @param string $file Dir or file.
     * @throws Error Internal error.
     */
    public function delete(string $file): void
    {
        if ($this->dir->is($file)) {
            $filenames = $this->dir->getFilenames($file, SCANDIR_SORT_NONE);

            if ($filenames === false)
                throw new Error(
                    "Can't get filenames from dir '$file'."
                );

            foreach ($filenames as $filename)
                if ($filename != "." && $filename != "..")
                    $this->delete("$file/$filename");

            if (!$this->dir->delete($file))
                throw new Error(
                    "Can't delete the directory '$file'."
                );

        } elseif ($this->file->is($file))
            if (!$this->file->unlink($file))
                throw new Error(
                    "Can't delete the file '$file'."
                );
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
        $directory = $dir . $path;

        while ($directory != $dir) {
            if ($this->dir->is($directory)) {
                $filenames = $this->dir->getFilenames($directory);

                if (isset($filenames[2]))
                    break;

                if (!$this->dir->delete($directory))
                    throw new Error(
                        "Can't delete the directory '$directory'."
                    );
            }

            $directory = dirname($directory);
        }
    }

    /**
     * Handles bus event.
     *
     * @param Cache $event Event.
     */
    protected function handleBusEvent(Cache $event): void
    {
        $this->state = $event->getDir();
    }
}