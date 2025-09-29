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

namespace Valvoid\Fusion\Dir;

use Valvoid\Fusion\Bus\Events\Cache;
use Valvoid\Fusion\Bus\Proxy\Proxy as BusProxy;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Fusion\Wrappers\Dir;

/**
 * Root package directory providing normalized filesystem operations.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Logic implements Proxy
{
    /** @var string Constant package root dir. */
    private string $root;

    /** @var string Dynamic package cache dir. */
    private string $cache;

    /**
     * Constructs the directory.
     *
     * @param array $config Config.
     * @param BusProxy $bus Event bus.
     * @param Dir $dir Abstract standard dir logic.
     * @param File $file Abstract standard file logic.
     * @throws Error Internal error exception.
     */
    public function __construct(
        private readonly Dir $dir,
        private readonly File $file,
        BusProxy $bus,
        array $config)
    {
        $this->root = $config["path"];

        $bus->addReceiver(static::class, $this->handleBusEvent(...),

            // new cache dir event
            Cache::class);

        // replace existing content with placeholder or
        // recycle package / empty dir content
        if ($dir->is($this->root))
            $config["clearable"] ?
                $this->replaceContent() :
                $this->recycleContent();

        // create new placeholder package
        elseif ($config["creatable"])
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

            // default placeholder cache
            $this->cache = "$this->root/cache";

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

        $path = array_key_exists("structure", $metadata) ?
            $this->getCachePath($metadata["structure"]) :
            null;

        if ($path === null)
            throw new Error(
                "Can't determine cache path from '$file'. The " .
                "directory is not empty and automatic deletion is not " .
                "authorized. Set 'clearable' to true to allow deletion "  .
                "of invalid content."
            );

        $this->cache = $this->root . $path;
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
        $this->cache = "$this->root/cache";
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
        $this->cache = "$this->root/cache";
    }

    /**
     * Returns the cache path defined in the given structure.
     *
     * @param array $struct Package structure.
     * @param string $breadcrumb Internal path prefix.
     * @return string|null Cache path, or null if not found.
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
     * Returns current (locked) task cache directory.
     *
     * @return string Directory.
     */
    public function getTaskDir(): string
    {
        return "$this->cache/task";
    }

    /**
     * Returns current (locked) task cache directory.
     *
     * @return string Directory.
     */
    public function getStateDir(): string
    {
        return "$this->cache/state";
    }

    /**
     * Returns absolute cache directory.
     *
     * @return string Directory.
     */
    public function getCacheDir(): string
    {
        return $this->cache;
    }

    /**
     * Returns other directory.
     *
     * @return string Directory.
     */
    public function getOtherDir(): string
    {
        return "$this->cache/other";
    }

    /**
     * Returns packages directory.
     *
     * @return string Directory.
     */
    public function getPackagesDir(): string
    {
        return "$this->cache/packages";
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
        $this->cache = $event->getDir();
    }
}