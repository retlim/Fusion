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

namespace Valvoid\Fusion\Tasks\Copy;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Dir\Dir as Directory;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Metadata\Internal\Category as InternalMetaCategory;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tasks\Task;
use Valvoid\Fusion\Util\Version\Interpreter;
use Valvoid\Fusion\Util\Version\Parser;
use Valvoid\Fusion\Wrappers\Dir;
use Valvoid\Fusion\Wrappers\File;

/**
 * Copy task to cache non-obsolete internal packages.
 */
class Copy extends Task
{
    /** @var string[] Generatable stateful dirs. */
    private array $lockedDirs = [];

    /**
     * Constructs the task.
     *
     * @param Box $box Dependency injection container.
     * @param Group $group Tasks group.
     * @param Log $log Event log.
     * @param Directory $directory Current working directory.
     * @param File $file Standard file logic wrapper.
     * @param Dir $dir Standard dir logic wrapper.
     * @param array $config Task config.
     */
    public function __construct(
        private readonly Box $box,
        private readonly Group $group,
        private readonly Log $log,
        private readonly Directory $directory,
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

        // only if remote external packages
        // do nothing if only internal as it is
        if (!$this->group->hasDownloadable())
            return;

        $internalMetas = $this->group->getInternalMetas();
        $externalMetas = $this->group->getExternalMetas();
        $packagesDir = $this->directory->getPackagesDir();

        foreach ($internalMetas as $id => $metadata)

            // copy recyclable and
            // moveable to state
            if ($metadata->getCategory() != InternalMetaCategory::OBSOLETE) {
                $from = $metadata->getSource();
                $to = "$packagesDir/$id";

                // lock unimportant dirs
                // cache directory
                $this->lockedDirs = [
                    $from . $metadata->getStatefulPath()
                ];

                // do not copy deps
                foreach ($metadata->getStructureSources() as $dir => $sources)
                    $this->lockedDirs[] = $from . $dir;

                $this->directory->createDir($to);
                $this->log->info(
                    $this->box->get(Content::class,
                        content: $metadata->getContent()));

                $this->copy($from, $to);

            // new version downloaded into indi state and
            // obsolete old exists in current state
            // trigger migrate hook
            } elseif (isset($externalMetas[$id])) {
                $parser = $this->box->get(Parser::class);
                $internalVersion = $parser::getInflatedVersion($metadata->getVersion());
                $externalVersion = $parser::getInflatedVersion($externalMetas[$id]->getVersion());

                // higher version must support
                // up and downgrade
                $this->box->get(Interpreter::class)
                    ::isBiggerThan($externalVersion, $internalVersion) ?
                        $externalMetas[$id]->onMigrate() :
                        $metadata->onMigrate();
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
            throw $this->box->get(Error::class,
                message: "Can't read directory '$from'."
            );

        foreach ($filenames as $filename)
            if ($filename != "." && $filename != "..") {
                $file = "$from/$filename";
                $copy = "$to/$filename";

                if ($this->file->is($file))
                    $this->directory->copy($file, $copy);

                // do not copy locked dirs
                elseif (!in_array($file, $this->lockedDirs)) {
                    $this->directory->createDir($copy);
                    $this->copy($file, $copy);
                }
            }
    }
}