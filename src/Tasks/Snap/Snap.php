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

namespace Valvoid\Fusion\Tasks\Snap;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Group\Group as GroupProxy;
use Valvoid\Fusion\Dir\Proxy as DirProxy;
use Valvoid\Fusion\Log\Proxy as LogProxy;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Metadata\External\External as ExternalMetadata;
use Valvoid\Fusion\Metadata\Internal\Internal as InternalMetadata;
use Valvoid\Fusion\Tasks\Task;
use Valvoid\Fusion\Wrappers\File;

/**
 * Snap task to persist current built state.
 */
class Snap extends Task
{
    /** @var array<string, ExternalMetadata> External metas. */
    private array $metas;

    /** @var array Implication. */
    private array $implication;

    /** @var array<string, string> Snapshot file content. */
    private array $content;

    /**
     * Constructs the task.
     *
     * @param Box $box Dependency injection container.
     * @param GroupProxy $group Tasks group.
     * @param LogProxy $log Event log.
     * @param DirProxy $directory Current working directory.
     * @param File $file Standard file logic wrapper.
     * @param array $config Task config.
     */
    public function __construct(
        private readonly Box $box,
        private readonly GroupProxy $group,
        private readonly LogProxy $log,
        private readonly DirProxy $directory,
        private readonly File $file,
        array $config)
    {
        parent::__construct($config);
    }

    /**
     * Executes the task.
     *
     * @throws Error Internal exception.
     */
    public function execute(): void
    {
        $this->log->info("persist implication and references");

        $this->implication = $this->group->getImplication();
        $this->metas = $this->group->getExternalMetas();

        // redundant state
        // refresh/create state file
        if (!$this->group->hasDownloadable()) {
            $cacheDir = $this->directory->getCacheDir();
            $metadata = $this->group->getRootMetadata();
            $id = $metadata->getId();

        } else {
            $metadata = $this->group->getRootMetadata();
            $id = $metadata->getId();
            $cacheDir = $this->directory->getPackagesDir() . "/$id" .
                $metadata->getStructureCache();
        }

        // do not cache root
        // only nested dependencies
        if (isset($this->implication[$id]))
            $this->implication = $this->implication[$id]["implication"];

        $this->directory->createDir($cacheDir);
        $this->log->info("production:");

        // common production
        // internal or external
        $this->addRootIds(
            $metadata->getProductionIds(),
            "$cacheDir/snapshot.json"
        );

        // internal root only
        // development
        if ($metadata instanceof InternalMetadata) {

            // local development
            $ids = $metadata->getLocalIds();
            $file = "$cacheDir/snapshot.local.json";

            if ($ids !== null) {
                $this->log->info("local:");
                $this->addRootIds($ids, $file);

            } else
                $this->directory->delete($file);

            // shared development
            $ids = $metadata->getDevelopmentIds();
            $file = "$cacheDir/snapshot.dev.json";

            if ($ids !== null) {
                $this->log->info("development:");
                $this->addRootIds($ids, $file);

            } else
                $this->directory->delete($file);
        }
    }

    /**
     * Adds root IDs.
     *
     * @param array $ids Root IDs.
     * @param string $file Absolute snapshot file.
     * @throws Error Internal exception.
     */
    private function addRootIds(array $ids, string $file): void
    {
        $this->log->verbose($file);
        $this->content = [];

        // production only
        // external recursive root
        foreach ($ids as $id) {
            $this->addNestedIds($this->implication[$id]["implication"]);
            $this->addContent($id);
        }

        $content = json_encode($this->content,

            // readable content
            JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);

        if (!$this->file->put($file, $content))
            throw new Error(
                "Can't write to the file \"$file\"."
            );
    }

    /**
     * Adds content.
     *
     * @param string $id ID.
     */
    private function addContent(string $id): void
    {
        $metadata = $this->metas[$id];
        $reference = $metadata->getSource()["reference"];
        $layers = $metadata->getLayers();

        // offset
        if (isset($layers["object"]["version"]))
            $reference = $layers["object"]["version"] . ":$reference";

        if (!isset($this->content[$id])) {
            $this->content[$id] = $reference;

            $this->log->info(
                $this->box->get(Content::class,
                    content: $metadata->getContent()));
        }
    }

    /**
     * Adds nested IDs.
     *
     * @param array $implication Implication.
     */
    private function addNestedIds(array $implication): void
    {
        foreach ($implication as $id => $entry) {
            $this->addNestedIds($entry["implication"]);
            $this->addContent($id);
        }
    }
}