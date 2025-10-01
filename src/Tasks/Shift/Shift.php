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

namespace Valvoid\Fusion\Tasks\Shift;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Events\Cache;
use Valvoid\Fusion\Bus\Events\Root;
use Valvoid\Fusion\Bus\Proxy as BusProxy;
use Valvoid\Fusion\Dir\Proxy as DirProxy;
use Valvoid\Fusion\Group\Group as GroupProxy;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Errors\Lifecycle;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Proxy as LogProxy;
use Valvoid\Fusion\Metadata\External\Category as ExternalMetaCategory;
use Valvoid\Fusion\Metadata\External\External as ExternalMeta;
use Valvoid\Fusion\Metadata\Internal\Category as InternalMetaCategory;
use Valvoid\Fusion\Metadata\Internal\Internal as InternalMeta;
use Valvoid\Fusion\Tasks\Task;
use Valvoid\Fusion\Wrappers\Dir;
use Valvoid\Fusion\Wrappers\File;

/**
 * Shift task to switch states.
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
    private array $lockedDirs = [];

    /** @var string[] Normalized file deletions. */
    private array $executedFiles = [];

    /**
     * Constructs the task.
     *
     * @param Box $box Dependency injection container.
     * @param BusProxy $bus Event bus.
     * @param GroupProxy $group Tasks group.
     * @param LogProxy $log Event log.
     * @param DirProxy $directory Current working directory.
     * @param File $file Standard file logic wrapper.
     * @param Dir $dir Standard dir logic wrapper.
     * @param array $config Task config.
     */
    public function __construct(
        private readonly Box $box,
        private readonly BusProxy $bus,
        private readonly GroupProxy $group,
        private readonly LogProxy $log,
        private readonly DirProxy $directory,
        private readonly File $file,
        private readonly Dir $dir,
        array $config)
    {
        parent::__construct($config);
    }

    /**
     * Executes the task.
     *
     * @throws Error Internal error.
     * @throws Lifecycle Lifecycle error.
     */
    public function execute(): void
    {
        $this->log->info("shift state");

        $this->root = $this->directory->getRootDir();

        // current state
        // rebuilt cache, ...
        if (!$this->group->hasDownloadable()) {
            foreach ($this->group->getInternalMetas() as $meta)
                if ($meta->getCategory() == InternalMetaCategory::OBSOLETE) {
                    $meta->onDelete();
                    $this->directory->delete($meta->getSource());
                    $this->directory->clear($this->root, $meta->getDir());

                } else $meta->onUpdate();

            return;
        }

        $this->internalMetas = $this->group->getInternalMetas();
        $this->state = $this->directory->getStateDir();
        $this->externalRootMeta = $this->group->getExternalRootMetadata();
        $this->externalMetas = $this->group->getExternalMetas();

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
            str_starts_with(str_replace('\\', '/', __DIR__),
                $this->root);

        // keep current package manager code alive
        // if inside working directory
        if ($hasInternalFusion)
            $this->persistCurrentCode(true);

        $internalCachePath = $this->group->getInternalRootMetadata()->getStructureCache();
        $externalCachePath = $this->externalRootMeta->getStructureCache();
        $this->lockedDirs = [
            $this->directory->getCacheDir() . "/log",
            $this->state,
            $this->directory->getPackagesDir(),
            $this->directory->getTaskDir(),
            $this->directory->getOtherDir()
        ];

        // before drop/clean up
        foreach ($this->internalMetas as $meta)
            if ($meta->getCategory() == InternalMetaCategory::OBSOLETE)
                $meta->onDelete();

        // delete current state
        // keep cached state dir and
        // executed files
        $this->cleanUpDir($this->root);

        // has internal cache dir - since storage is outside
        // - init build may have no dir
        // new cache directory
        if ($this->dir->is($this->root . $internalCachePath) &&
            $internalCachePath != $externalCachePath) {
            $oldCacheDir = $this->root . $internalCachePath;
            $newCacheDir = $this->root . $externalCachePath;

            // notify new cache directory change
            $this->bus->broadcast(
                $this->box->get(Cache::class,
                    dir: $newCacheDir));

            // keep current code session alive for next tasks
            if ($hasInternalFusion)
                $this->bus->broadcast(
                    $this->box->get(Root::class,
                        dir: $this->directory->getOtherDir() . "/valvoid/fusion"));

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
                } while ($this->file->exists($tempCacheDir));

                // rename old to temp
                // clear old ballast leading dirs
                // parent dir must exist
                // rename temp to new
                $this->directory->rename($oldCacheDir, $tempCacheDir);
                $this->directory->delete($cachePrefixDir);
                $this->directory->createDir($newCacheDir);
                $this->directory->rename($tempCacheDir, $newCacheDir);

            } else {

                // parent dir must exist
                // rename old to new
                // clear old ballast leading dirs
                $this->directory->createDir($newCacheDir);
                $this->directory->rename($oldCacheDir, $newCacheDir);
                $this->directory->delete($cachePrefixDir);
            }

            // update changed
            $this->state = $this->directory->getStateDir();
        }

        $this->shiftDirectory($this->state, $this->root);

        // downloaded or
        // moved rebuilt to new directory -> "downloaded" from internal directory
        foreach ($this->externalMetas as $id => $meta)
            if ($meta->getCategory() == ExternalMetaCategory::DOWNLOADABLE ||
                $this->internalMetas[$id]->getCategory() === InternalMetaCategory::MOVABLE) {
                $meta->onInstall();
                $this->log->info(
                    $this->box->get(Content::class,
                        content: $meta->getContent()));
            }

        // rebuilt cache, state, ...
        foreach ($this->internalMetas as $meta)
            if ($meta->getCategory() === InternalMetaCategory::RECYCLABLE) {
                $meta->onUpdate();
                $this->log->info(
                    $this->box->get(Content::class,
                        content: $meta->getContent()));
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
        $stateDir = $this->directory->getStateDir();
        $this->lockedDirs = [];

        foreach ($this->internalMetas as $id => $metadata) {

            // keep current package manager code alive
            // if inside working directory
            if ($id == "valvoid/fusion" &&

                // normalize dir
                str_starts_with(str_replace('\\', '/', __DIR__),
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
                    $this->directory->delete($to);
                    $this->directory->rename($from, $to);

                // root
                // keep static content
                // state, log, ... directory
                } else {
                    $this->lockedDirs += [
                        "$to/log",
                        $this->state,
                        $this->directory->getPackagesDir(),
                        $this->directory->getTaskDir(),
                        $this->directory->getOtherDir()
                    ];

                    // temp files are now outside the cache dir
                    // if this is a new build from fusion.json the
                    // target dir may not exist yet
                    if ($this->file->exists($to))
                        $this->cleanUpDir($to);

                    else $this->dir->create($to);

                    $this->copyDir($from, $to);
                }

                // refresh extensions
                foreach ($metadata->getStructureExtensions() as $extension) {
                    $to = $metadata->getSource() . $extension;
                    $from = "$stateDir$dir$extension";

                    // extension is optional
                    if ($this->dir->is($from)) {
                        $this->directory->delete($to);
                        $this->directory->rename($from, $to);
                    }
                }

                // refresh states
                foreach ($metadata->getStructureStates() as $state) {
                    $to = $metadata->getSource() . $state;
                    $from = "$stateDir$dir$state";

                    // state is optional
                    if ($this->dir->is($from)) {
                        $this->directory->delete($to);
                        $this->directory->rename($from, $to);
                    }
                }

                // rebuilt cache, state, ...
                $metadata->onUpdate();
                $this->log->info(
                    $this->box->get(Content::class,
                        content: $metadata->getContent()));

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

            if (!$this->file->exists($to))
                $this->directory->createDir($to);

            $this->shiftDirectory(
                $stateDir . $dir,
                $to
            );

            $metadata->onInstall();
            $this->log->info(
                $this->box->get(Content::class,
                     content: $metadata->getContent()));
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
        $to = $this->directory->getOtherDir() . "/valvoid/fusion";
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
                $this->directory->getPackagesDir(),
                $this->directory->getTaskDir(),
                $this->directory->getOtherDir()
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

        $this->directory->createDir($to);

        $this->copyDir($from, $to);

        // notify new fusion code root
        $this->bus->broadcast(
            $this->box->get(Root::class,
                dir: $to));
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
        $filenames = $this->dir->getFilenames($from, SCANDIR_SORT_NONE);

        if ($filenames === false)
            throw new Error(
                "Can't read directory '$from'."
            );

        foreach ($filenames as $filename)
            if ($filename != "." && $filename != "..") {
                $source = "$from/$filename";
                $target = "$to/$filename";

                if ($this->dir->is($source))
                    if ($this->file->exists($target))
                        $this->shiftDirectory($source, $target);

                    else {
                        $this->directory->createDir($target);
                        $this->directory->rename($source, $target);
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
            $content = $this->file->get($from);

            if ($content === false)
                throw new Error(
                    "Can't read for executed file \"$from\"."
                );

            if ($this->file->put($to, $content) === false)
                throw new Error(
                    "Can't write to executed file \"$to\"."
                );

        } else
            $this->directory->rename($from, $to);
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
        $filenames = $this->dir->getFilenames($from, SCANDIR_SORT_NONE);

        if ($filenames === false)
            throw new Error(
                "Can't read directory '$from'."
            );

        foreach ($filenames as $filename)
            if ($filename != "." && $filename != "..") {
                $file = "$from/$filename";
                $copy = "$to/$filename";

                if ($this->file->is($file))
                    $this->directory->copy($file, $copy);

                // do not copy locked dirs
                // cache and source
                elseif (!in_array($file, $this->lockedDirs)) {
                    $this->directory->createDir($copy);
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
        $filenames = $this->dir->getFilenames($dir, SCANDIR_SORT_NONE);

        if ($filenames === false)
            throw new Error(
                "Can't read directory '$dir'."
            );

        foreach ($filenames as $filename)
            if ($filename != "." && $filename != "..") {
                $file = "$dir/$filename";

                if ($this->dir->is($file)) {
                    if (!in_array($file, $this->lockedDirs))
                        $this->cleanUpDir($file);

                // normalized executed file deletion
                // replace content
                } elseif (in_array($file, $this->executedFiles)) {
                    if ($this->file->put($file, "") === false)
                        throw new Error(
                            "Can't clear executed file '$file'."
                        );

                } else $this->directory->delete($file);
            }

        foreach ($this->lockedDirs as $lockedDir)
            if (str_starts_with($lockedDir, $dir))
                return;

        $this->directory->delete($dir);
    }
}