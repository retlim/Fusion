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

namespace Valvoid\Fusion\Tasks\Copy;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Group\Group as GroupProxy;
use Valvoid\Fusion\Dir\Proxy as DirProxy;
use Valvoid\Fusion\Log\Proxy as LogProxy;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Metadata\Internal\Category as InternalMetaCategory;
use Valvoid\Fusion\Tasks\Task;
use Valvoid\Fusion\Util\Version\Interpreter;
use Valvoid\Fusion\Util\Version\Parser;
use Valvoid\Fusion\Wrappers\Dir;
use Valvoid\Fusion\Wrappers\File;

/**
 * Copy task to cache non-obsolete internal packages.
 *
 * @Copyright Valvoid
 * @license GNU GPLv3
 */
class Copy extends Task
{
    /** @var string[] Cache and nested source dirs. */
    private array $lockedDirs = [];

    /**
     * Constructs the task.
     *
     * @param Box $box Dependency injection container.
     * @param GroupProxy $group Tasks group.
     * @param LogProxy $log Event log.
     * @param DirProxy $directory Current working directory.
     * @param File $file Standard file logic wrapper.
     * @param Dir $dir Standard dir logic wrapper.
     * @param array $config Task config.
     */
    public function __construct(
        private readonly Box $box,
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
     */
    public function execute(): void
    {
        $this->log->info("cache internal packages");

        // only if remote external
        // do nothing if only internal as it is
        if (!$this->group->hasDownloadable())
            return;

        $internalMetas = $this->group->getInternalMetas();
        $externalMetas = $this->group->getExternalMetas();
        $packagesDir = $this->directory->getPackagesDir();

        // potential extensions paths to
        // copy only existing packages
        // take external IDs because special cases like clone
        // after clone repo there are maybe committed pseudo extensions
        // no internal packages yet
        // identify them by external valid IDs
        $pseudoIds = array_keys($externalMetas);

        foreach ($pseudoIds as $i => $id)
            if (isset($internalMetas[$id]))
                unset($pseudoIds[$i]);

        // copy recyclable and moveable to state
        foreach ($internalMetas as $id => $metadata)
            if ($metadata->getCategory() != InternalMetaCategory::OBSOLETE) {
                $from = $metadata->getSource();
                $to = "$packagesDir/$id";

                // lock unimportant dirs
                // cache directory
                $this->lockedDirs = [
                    $from . $metadata->getStructureCache()
                ];

                $this->directory->createDir($to);
                $this->log->info(
                    $this->box->get(Content::class,
                        content: $metadata->getContent()));

                // nested source wrapper directories
                // "delete"/ignore obsolete package extensions
                foreach ($metadata->getStructureSources() as $dir => $source)
                    if ($dir) {
                        $this->lockedDirs[] = $from . $dir;

                        // copy pseudos
                        foreach ($pseudoIds as $pseudoId) {
                            $extension = "$from$dir/$pseudoId";

                            if ($this->dir->is($extension)) {
                                $this->directory->createDir("$to$dir/$pseudoId");
                                $this->copy($extension, "$to$dir/$pseudoId");
                            }
                        }
                    }

                // clear obsolete extensions
                // copy only valid
                foreach ($metadata->getStructureExtensions() as $dir) {
                    $this->lockedDirs[] = $from . $dir;

                    foreach ($internalMetas as $extenderId => $extender)
                        if ($extender->getCategory() != InternalMetaCategory::OBSOLETE) {
                            $extension = "$from$dir/$extenderId";

                            if ($this->dir->is($extension)) {
                                $this->directory->createDir("$to$dir/$extenderId");
                                $this->copy($extension, "$to$dir/$extenderId");
                            }
                        }
                }

                // content
                $this->copy($from, $to);

            // new version
            // migrate
            // keep persistence
            } elseif (isset($externalMetas[$id])) {
                $parser = $this->box->get(Parser::class);
                $internalVersion = $parser::getInflatedVersion($metadata->getVersion());
                $externalVersion = $parser::getInflatedVersion($externalMetas[$id]->getVersion());

                // higher version must support
                // up and downgrade
                if ($this->box->get(Interpreter::class)
                    ::isBiggerThan($externalVersion, $internalVersion) ?
                        $externalMetas[$id]->onMigrate() :
                        $metadata->onMigrate())

                    // indicator result
                    continue;

                $extensions = $externalMetas[$id]->getStructureExtensions();

                // no custom migration script
                // default fallback migration
                // non-breaking changes or
                if ($internalVersion["major"] == $externalVersion["major"] ||

                    // same extension directories
                    !array_diff($metadata->getStructureExtensions(), $extensions)) {
                    $from = $metadata->getSource();
                    $to = "$packagesDir/$id";

                    foreach ($extensions as $dir)
                        foreach ($internalMetas as $extenderId => $extender)
                            if ($extender->getCategory() != InternalMetaCategory::OBSOLETE) {
                                $extension = "$from$dir/$extenderId";

                                if ($this->dir->is($extension)) {
                                    $this->directory->createDir("$to$dir/$extenderId");
                                    $this->copy($extension, "$to$dir/$extenderId");
                                }
                            }
                }
            }
    }

    /**
     * Copies content from one to other directory.
     *
     * @param string $from Origin directory.
     * @param string $to Cache directory.
     * @throws Error
     */
    private function copy(string $from, string $to): void
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
                    $this->copy($file, $copy);
                }
            }
    }
}