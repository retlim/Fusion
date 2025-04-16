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

namespace Valvoid\Fusion\Tasks\Shift;

use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Cache;
use Valvoid\Fusion\Bus\Events\Root;
use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Errors\Lifecycle;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Metadata\External\Category as ExternalMetaCategory;
use Valvoid\Fusion\Metadata\External\External as ExternalMeta;
use Valvoid\Fusion\Metadata\Internal\Category as InternalMetaCategory;
use Valvoid\Fusion\Metadata\Internal\Internal as InternalMeta;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tasks\Task;

/**
 * Shift task.
 *
 * @Copyright Valvoid
 * @license GNU GPLv3
 */
class Shift extends Task
{
    /** @var string Working directory root. */
    private string $root;

    /** @var ?ExternalMeta Recursive external root meta. */
    private ?ExternalMeta $externalRootMeta;

    /** @var string State directory. */
    private string $state;

    /** @var InternalMeta[] Internal metas. */
    private array $internalMetas;

    /** @var ExternalMeta[] External metas. */
    private array $externalMetas;

    /** @var string[] Cache and nested source dirs. */
    private array $lockedDirs;

    /** @var string[] Normalized file deletions. */
    private array $executedFiles = [];

    /** @var string Current directory helper. */
    private string $dir = __DIR__;

    /**
     * Executes the task.
     *
     * @throws Error Internal error.
     * @throws Lifecycle Lifecycle error.
     */
    public function execute(): void
    {
        Log::info("shift state");

        // current state
        // rebuilt cache, ...
        if (!Group::hasDownloadable()) {
            foreach (Group::getInternalMetas() as $meta)
                $meta->onUpdate();

            return;
        }

        $this->root = Dir::getRootDir();
        $this->internalMetas = Group::getInternalMetas();
        $this->state = Dir::getStateDir();
        $this->externalRootMeta = Group::getExternalRootMetadata();
        $this->externalMetas = Group::getExternalMetas();

        // complete recursive or
        // pick nested
        ($this->externalRootMeta &&
            $this->externalRootMeta->getCategory() == ExternalMetaCategory::DOWNLOADABLE) ?
            $this->shiftRecursive() :
            $this->shiftNested();
    }

    /**
     * Shifts recursive state.
     *
     * @throws Error Internal error.
     * @throws Lifecycle Lifecycle error.
     */
    private function shiftRecursive(): void
    {
        $hasInternalFusion = isset($this->internalMetas["valvoid/fusion"]) &&

            // normalize dir
            str_starts_with(str_replace('\\', '/', $this->dir),
                $this->root);

        // keep current package manager code alive
        // if inside working directory
        if ($hasInternalFusion)
            $this->persistCurrentCode(true);

        $internalCachePath = Group::getInternalRootMetadata()->getStructureCache();
        $externalCachePath = $this->externalRootMeta->getStructureCache();
        $this->lockedDirs = [
            Dir::getCacheDir() . "/log",
            $this->state,
            Dir::getPackagesDir(),
            Dir::getTaskDir(),
            Dir::getOtherDir()
        ];

        // before drop/clean up
        foreach ($this->internalMetas as $meta)
            if ($meta->getCategory() == InternalMetaCategory::OBSOLETE)
                $meta->onDelete();

        // delete current state
        // keep cached state dir and
        // executed files
        $this->cleanUpDir($this->root);

        // new cache directory
        if ($internalCachePath != $externalCachePath) {
            $oldCacheDir = $this->root . $internalCachePath;
            $newCacheDir = $this->root . $externalCachePath;

            // notify new cache directory change
            Bus::broadcast(new Cache($newCacheDir));

            // keep current code session alive for next tasks
            if ($hasInternalFusion)
                Bus::broadcast(new Root(Dir::getOtherDir() .
                    "/valvoid/fusion"));

            // [0] is empty due to leading slash
            $internalCachePathParts = explode('/', $internalCachePath);
            $cachePrefix = "/$internalCachePathParts[1]";
            $cachePrefixDir = $this->root . $cachePrefix;

            // handle intersection
            if (str_starts_with($externalCachePath, $cachePrefix)) {
                $tempCacheDir = $cachePrefixDir;

                do {
                    $tempCacheDir .= "-";

                // search temp non-existing cache dir
                } while (file_exists($tempCacheDir));

                // rename old to temp
                // clear old ballast leading dirs
                // parent dir must exist
                // rename temp to new
                Dir::rename($oldCacheDir, $tempCacheDir);
                Dir::delete($cachePrefixDir);
                Dir::createDir($newCacheDir);
                Dir::rename($tempCacheDir, $newCacheDir);

            } else {

                // parent dir must exist
                // rename old to new
                // clear old ballast leading dirs
                Dir::createDir($newCacheDir);
                Dir::rename($oldCacheDir, $newCacheDir);
                Dir::delete($cachePrefixDir);
            }

            // update changed
            $this->state = Dir::getStateDir();
        }

        $this->shiftDirectory($this->state, $this->root);

        // downloaded or
        // moved rebuilt to new directory -> "downloaded" from internal directory
        foreach ($this->externalMetas as $id => $meta)
            if ($meta->getCategory() == ExternalMetaCategory::DOWNLOADABLE ||
                $this->internalMetas[$id]->getCategory() === InternalMetaCategory::MOVABLE) {
                Log::info(new Content($meta->getContent()));
                $meta->onInstall();
            }

        // rebuilt cache, state, ...
        foreach ($this->internalMetas as $meta)
            if ($meta->getCategory() === InternalMetaCategory::RECYCLABLE) {
                Log::info(new Content($meta->getContent()));
                $meta->onUpdate();
            }
    }

    /**
     * Shifts nested state.
     *
     * @throws Error Internal error.
     * @throws Lifecycle Lifecycle error.
     */
    private function shiftNested(): void
    {
        $stateDir = Dir::getStateDir();
        $this->lockedDirs = [];

        foreach ($this->internalMetas as $id => $metadata) {

            // keep current package manager code alive
            // if inside working directory
            if ($id == "valvoid/fusion" &&

                // normalize dir
                str_starts_with(str_replace('\\', '/', $this->dir),
                    $this->root))
                $this->persistCurrentCode(false);

            // recycle
            // override only cache
            if ($metadata->getCategory() == InternalMetaCategory::RECYCLABLE) {
                $dir = $metadata->getDir();
                $cache = $metadata->getStructureCache();
                $from = "$stateDir$dir$cache";
                $to = $metadata->getSource() . $cache;

                // clear nested
                if ($dir) {
                    Dir::delete($to);
                    Dir::rename($from, $to);

                // root
                // keep static content
                // state, log, ... directory
                } else {
                    $this->lockedDirs += [
                        "$to/log",
                        $this->state,
                        Dir::getPackagesDir(),
                        Dir::getTaskDir(),
                        Dir::getOtherDir()
                    ];

                    $this->cleanUpDir($to);
                    $this->copyDir($from, $to);
                }

                // refresh extensions
                foreach ($metadata->getStructureExtensions() as $extension) {
                    $to = $metadata->getSource() . $extension;
                    $from = "$stateDir$dir$extension";

                    // extension is optional
                    if (is_dir($from)) {
                        Dir::delete($to);
                        Dir::rename($from, $to);
                    }
                }

                // refresh states
                foreach ($metadata->getStructureStates() as $state) {
                    $to = $metadata->getSource() . $state;
                    $from = "$stateDir$dir$state";

                    // state is optional
                    if (is_dir($from)) {
                        Dir::delete($to);
                        Dir::rename($from, $to);
                    }
                }

                // rebuilt cache, state, ...
                $metadata->onUpdate();
                Log::info(new Content($metadata->getContent()));

            // clean up
            // delete obsolete and movable
            } else {
                $metadata->onDelete();
                $this->cleanUpDir($metadata->getSource());
            }
        }

        // shift
        // rename loadable and movable
        // downloaded or
        // moved rebuilt to new directory -> "downloaded" from internal directory
        foreach ($this->externalMetas as $id => $metadata)
            if ($metadata->getCategory() == ExternalMetaCategory::DOWNLOADABLE ||
                $this->internalMetas[$id]->getCategory() === InternalMetaCategory::MOVABLE) {

            $dir = $metadata->getDir();
            $to = $this->root . $dir;

            if (!file_exists($to))
                Dir::createDir($to);

            $this->shiftDirectory(
                $stateDir . $dir,
                $to
            );

            $metadata->onInstall();
            Log::info(new Content($metadata->getContent()));
        }
    }

    /**
     * Shifts current package manager code to the /other dir.
     *
     * @param bool $recursive
     * @throws Error
     */
    private function persistCurrentCode(bool $recursive): void
    {
        $meta = $this->internalMetas["valvoid/fusion"];
        $to = Dir::getOtherDir() . "/valvoid/fusion";
        $from = $meta->getSource();

        // recursive root
        // lock state and packages
        if ($recursive) {

            // same directory
            // normalize executed file "fusion" deletion
            // keep file open and replace content
            if ($meta->getCategory() == InternalMetaCategory::RECYCLABLE)
                $this->executedFiles[] = "$from/fusion";

            elseif (isset($this->externalMetas["valvoid/fusion"])) {
                $externalMeta = $this->externalMetas["valvoid/fusion"];

                // different version in same nested directory
                // keep executed file and
                // replace content
                if ($externalMeta->getCategory() == ExternalMetaCategory::DOWNLOADABLE &&
                    $meta->getDir() == $externalMeta->getDir())
                    $this->executedFiles[] = "$from/fusion";
            }

            // lock unimportant dirs
            // cache directory
            $this->lockedDirs = [
                $this->state,
                Dir::getPackagesDir(),
                Dir::getTaskDir(),
                Dir::getOtherDir()
            ];

            // nested source wrapper directories
            // actually only if top
            // nested has no sources inside
            foreach ($meta->getStructureSources() as $dir => $source)
                if ($dir)
                    $this->lockedDirs[] = $from . $dir;

        // not recycle -> generated files only
        // not movable -> new directory
        // downloadable
        } elseif (isset($this->externalMetas["valvoid/fusion"])) {
            $externalMeta = $this->externalMetas["valvoid/fusion"];

            // different version in same nested directory
            // keep executed file and
            // replace content
            if ($externalMeta->getCategory() == ExternalMetaCategory::DOWNLOADABLE &&
                $meta->getDir() == $externalMeta->getDir()) {
                $this->lockedDirs[] = $from;
                $this->executedFiles[] = "$from/fusion";
            }
        }

        Dir::createDir($to);

        $this->copyDir($from, $to);

        // notify new fusion code root
        Bus::broadcast(new Root($to));
    }

    /**
     * Shifts a directory.
     *
     * @param string $from Source directory.
     * @param string $to Target directory.
     * @throws Error Invalid directory error.
     */
    private function shiftDirectory(string $from, string $to): void
    {
        $filenames = scandir($from, SCANDIR_SORT_NONE);

        if ($filenames === false)
            throw new Error(
                "Can't read directory \"$from\"."
            );

        foreach ($filenames as $filename)
            if ($filename != "." && $filename != "..") {
                $source = "$from/$filename";
                $target = "$to/$filename";

                if (is_dir($source))
                    if (file_exists($target))
                        $this->shiftDirectory($source, $target);

                    else {
                        Dir::createDir($target);
                        Dir::rename($source, $target);
                    }

                else
                    $this->shiftFile($source, $target);
            }
    }

    /**
     * Shifts a file.
     *
     * @param string $from Source file.
     * @param string $to Target file.
     * @throws Error Invalid file error.
     */
    private function shiftFile(string $from, string $to): void
    {
        // normalized
        // keep executed file and replace content
        if (in_array($to, $this->executedFiles)) {
            $content = file_get_contents($from);

            if ($content === false)
                throw new Error(
                    "Can't read for executed file \"$from\"."
                );

            if (file_put_contents($to, $content) === false)
                throw new Error(
                    "Can't write to executed file \"$to\"."
                );

        } else
            Dir::rename($from, $to);
    }

    /**
     * Copies content from one to other directory.
     *
     * @param string $from Origin directory.
     * @param string $to Cache directory.
     * @throws Error Internal error.
     */
    private function copyDir(string $from, string $to): void
    {
        foreach (scandir($from, SCANDIR_SORT_NONE) as $filename)
            if ($filename != "." && $filename != "..") {
                $file = "$from/$filename";
                $copy = "$to/$filename";

                if (is_file($file))
                    Dir::copy($file, $copy);

                // do not copy locked dirs
                // cache and source
                elseif (!in_array($file, $this->lockedDirs)) {
                    Dir::createDir($copy);
                    $this->copyDir($file, $copy);
                }
            }
    }

    /**
     * Cleans up directory.
     *
     * @param string $dir Directory.
     * @throws Error Internal error.
     */
    private function cleanUpDir(string $dir): void
    {
        foreach (scandir($dir, SCANDIR_SORT_NONE) as $filename)
            if ($filename != "." && $filename != "..") {
                $file = "$dir/$filename";

                if (is_dir($file)) {
                    if (!in_array($file, $this->lockedDirs))
                        $this->cleanUpDir($file);

                // normalized executed file deletion
                // replace content
                } elseif (in_array($file, $this->executedFiles)) {
                    if (file_put_contents($file, "") === false)
                        throw new Error(
                            "Can't clear executed file \"$file\"."
                        );

                } else
                    Dir::delete($file);
            }

        foreach ($this->lockedDirs as $lockedDir)
            if (str_starts_with($lockedDir, $dir))
                return;

        Dir::delete($dir);
    }
}