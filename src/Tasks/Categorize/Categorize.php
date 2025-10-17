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

namespace Valvoid\Fusion\Tasks\Categorize;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Group\Group as GroupProxy;
use Valvoid\Fusion\Log\Proxy as LogProxy;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Metadata\External\Category as ExternalMetaCategory;
use Valvoid\Fusion\Metadata\External\External;
use Valvoid\Fusion\Metadata\Internal\Category as InternalMetaCategory;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Tasks\Task;

/**
 * Categorize task to sort metas.
 */
class Categorize extends Task
{
    /** @var array<string, Internal> Internal metas. */
    private array $metas = [];

    /** @var bool Has recursive root indicator. */
    private bool $hasExternalRoot = false;

    /** @var array<string, External> Loadable category wrapper. */
    private array $loadable = [];

    /** @var array<string, Internal> Obsolete category wrapper. */
    private array $obsolete = [];

    /** @var array<string, Internal> Recyclable category wrapper. */
    private array $recyclable = [];

    /** @var array<string, Internal> Movable category wrapper. */
    private array $movable = [];

    /**
     * Constructs the task.
     *
     * @param Box $box Dependency injection container.
     * @param GroupProxy $group Tasks group.
     * @param LogProxy $log Event log.
     * @param array $config Task config.
     */
    public function __construct(
        private readonly Box $box,
        private readonly GroupProxy $group,
        private readonly LogProxy $log,
        array $config)
    {
        parent::__construct($config);
    }

    /**
     * Executes the task.
     */
    public function execute(): void
    {
        $this->metas = $this->group->getInternalMetas();

        $this->config["efficiently"] ?
            $this->categorizeEfficiently() :
            $this->categorizeRedundant();
    }

    /** Categorizes redundant. */
    private function categorizeRedundant(): void
    {
        $this->log->info("categorize metas redundant");

        // identifier is common then
        foreach ($this->group->getExternalMetas() as $id => $meta) {
            $dir = $meta->getDir();
            $this->loadable[$id] = $meta;

            $meta->setCategory(

                // download new
                ExternalMetaCategory::DOWNLOADABLE);

            if (isset($this->metas[$id])) {
                $this->obsolete[$id] = $this->metas[$id];

                // remove for lazy rest loop
                unset($this->metas[$id]);
                $this->obsolete[$id]->setCategory(

                    // different versions or not
                    // drop required
                    InternalMetaCategory::OBSOLETE);
            }

            // empty director = root
            if (!$this->hasExternalRoot && !$dir)
                $this->hasExternalRoot = true;
        }

        $this->handleRest();
        $this->notify();
    }

    /** Categorizes efficiently. */
    private function categorizeEfficiently(): void
    {
        $this->log->info("categorize metas efficiently");

        // identifier is common then
        foreach ($this->group->getExternalMetas() as $id => $meta) {
            $dir = $meta->getDir();

            // empty director = root
            if (!$this->hasExternalRoot && !$dir)
                $this->hasExternalRoot = true;

            // decision
            if (isset($this->metas[$id])) {
                if ($meta->getVersion() == $this->metas[$id]->getVersion()) {
                    if ($dir != $this->metas[$id]->getDir()) {
                        $this->movable[$id] = $this->metas[$id];
                        $this->metas[$id]->setCategory(

                            // keep and
                            // move to other direction
                            InternalMetaCategory::MOVABLE);

                    } else {
                        $this->recyclable[$id] = $this->metas[$id];
                        $this->metas[$id]->setCategory(

                            // do nothing
                            // keep as it is
                            InternalMetaCategory::RECYCLABLE);
                    }

                    $meta->setCategory(

                        // do nothing
                        ExternalMetaCategory::REDUNDANT);

                } else {
                    $this->obsolete[$id] = $this->metas[$id];
                    $this->loadable[$id] = $meta;

                    $this->metas[$id]->setCategory(

                        // different versions
                        // other required
                        InternalMetaCategory::OBSOLETE);

                    $meta->setCategory(

                        // download new
                        ExternalMetaCategory::DOWNLOADABLE);
                }

                // remove for lazy rest loop
                unset($this->metas[$id]);
                continue;
            }

            $this->loadable[$id] = $meta;
            $meta->setCategory(

                // download new
                ExternalMetaCategory::DOWNLOADABLE);
        }

        $this->handleRest();
        $this->notify();
    }

    /** Handles rest. */
    private function handleRest(): void
    {
        // handle rest
        foreach ($this->metas as $id => $meta) {
            if (!$this->hasExternalRoot && !$meta->getDir()) {
                $this->recyclable[$id] = $meta;
                $meta->setCategory(

                    // recycle current root
                    InternalMetaCategory::RECYCLABLE);

                continue;
            }

            $this->obsolete[$id] = $meta;
            $meta->setCategory(

                // just drop
                InternalMetaCategory::OBSOLETE);
        }
    }

    /** Notifies result. */
    private function notify(): void
    {
        if ($this->recyclable) {
            $this->log->info("recycle:");

            foreach ($this->recyclable as $meta)
                $this->log->info(
                    $this->box->get(Content::class,
                        content: $meta->getContent()));
        }

        if ($this->loadable) {
            $this->log->info("download:");

            foreach ($this->loadable as $meta)
                $this->log->info(
                    $this->box->get(Content::class,
                        content: $meta->getContent()));
        }

        if ($this->obsolete) {
            $this->log->info("delete:");

            foreach ($this->obsolete as $meta)
                $this->log->info(
                    $this->box->get(Content::class,
                        content: $meta->getContent()));
        }

        if ($this->movable) {
            $this->log->info("move:");

            foreach ($this->movable as $meta)
                $this->log->info(
                    $this->box->get(Content::class,
                        content: $meta->getContent()));
        }
    }
}