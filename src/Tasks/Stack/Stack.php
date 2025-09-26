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

namespace Valvoid\Fusion\Tasks\Stack;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Group\Group as GroupProxy;
use Valvoid\Fusion\Dir\Proxy as DirProxy;
use Valvoid\Fusion\Log\Proxy as LogProxy;
use Valvoid\Fusion\Metadata\External\Category as ExternalMetaCategory;
use Valvoid\Fusion\Metadata\External\External;
use Valvoid\Fusion\Metadata\Internal\Category as InternalMetaCategory;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Tasks\Task;

/**
 * Stack task to merge individual packages.
 *
 * @Copyright Valvoid
 * @license GNU GPLv3
 */
class Stack extends Task
{
    /** @var External[] External metas. */
    private array $metas = [];

    /**
     * Constructs the task.
     *
     * @param Box $box Dependency injection container.
     * @param GroupProxy $group Tasks group.
     * @param LogProxy $log Event log.
     * @param DirProxy $directory Current working directory.
     * @param array $config Task config.
     */
    public function __construct(
        private readonly Box $box,
        private readonly GroupProxy $group,
        private readonly LogProxy $log,
        private readonly DirProxy $directory,
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
        $this->log->info("stack new state");

        if (!$this->group->hasDownloadable())
            return;

        $stateDir = $this->directory->getStateDir();
        $packageDir = $this->directory->getPackagesDir();
        $this->metas = $this->group->getExternalMetas();
        $rootMetadata = $this->group->getExternalRootMetadata() ??
            $this->group->getInternalRootMetadata();

        $this->directory->createDir($stateDir);
        $this->log->info(
            $this->box->get(Content::class,
                content: $rootMetadata->getContent()));
        $this->directory->rename("$packageDir/" . $rootMetadata->getId(),
            $stateDir
        );

        // nested internal
        foreach ($this->group->getInternalMetas() as $id => $meta) {
            $category = $meta->getCategory();

            if ($category == InternalMetaCategory::OBSOLETE)
                continue;

            $dir = $meta->getDir();

            // jump over root
            if (!$dir)
                continue;

            $this->log->info(
                $this->box->get(Content::class,
                    content: $meta->getContent()));

            // take new directory
            if ($category == InternalMetaCategory::MOVABLE)
                $dir = $this->metas[$id]->getDir();

            $to = $stateDir . $dir;

            $this->directory->createDir($to);
            $this->directory->rename("$packageDir/$id", $to);
        }

        // nested external
        foreach ($this->metas as $id => $meta) {
            if ($meta->getCategory() == ExternalMetaCategory::DOWNLOADABLE &&
                $meta->getDir()) {
                $this->log->info(
                    $this->box->get(Content::class,
                        content: $meta->getContent()));

                $to = $stateDir . $meta->getDir();

                $this->directory->createDir($to);
                $this->directory->rename("$packageDir/$id", $to);
            }
        }

        // trigger lifecycle callbacks
        $this->triggerLifecycleCallbacks($this->group->getImplication());

        // root fallback
        // no recursive or source
        if ($rootMetadata instanceof Internal)
            $rootMetadata->onCopy();
    }

    /**
     * Triggers lifecycle callbacks.
     *
     * @param array $implication Implication.
     */
    private function triggerLifecycleCallbacks(array $implication): void
    {
        foreach ($implication as $id => $entry) {

            // nested first
            // respect implication order
            $this->triggerLifecycleCallbacks($entry["implication"]);

            $metadata = $this->metas[$id];

            $metadata->getCategory() == ExternalMetaCategory::DOWNLOADABLE ?
                $metadata->onDownload() :
                $metadata->onCopy();
        }
    }
}