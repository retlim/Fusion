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

namespace Valvoid\Fusion\Tasks\Extend;

use Exception;
use Valvoid\Box\Box;
use Valvoid\Fusion\Dir\Dir as Directory;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Metadata\External\Category as ExternalMetaCategory;
use Valvoid\Fusion\Metadata\External\External as ExternalMeta;
use Valvoid\Fusion\Metadata\Internal\Category as InternalMetaCategory;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tasks\Task;
use Valvoid\Fusion\Wrappers\File;

/**
 * Task that handles package extensions.
 */
class Extend extends Task
{
    /** @var ExternalMeta[] External metas.  */
    private array $externalMetas;

    /** @var string Packages cache directory. */
    private string $packagesDir;

    /** @var array Sorted identifiers. */
    private array $indexes = [];

    /** @var array  Structures. */
    private array $structures = [];

    /**
     * Constructs the task.
     *
     * @param Box $box Dependency injection container.
     * @param Group $group Tasks group.
     * @param Log $log Event log.
     * @param Directory $directory Current working directory.
     * @param File $file Standard file logic wrapper.
     * @param array $config Task config.
     */
    public function __construct(
        private readonly Box $box,
        private readonly Group $group,
        private readonly Log $log,
        private readonly Directory $directory,
        private readonly File $file,
        array $config)
    {
        parent::__construct($config);
    }

    /**
     * Executes the task.
     *
     * @throws Error Internal error.
     * @throws Exception
     */
    public function execute(): void
    {
        if ($this->group->getExternalRootMetadata())
            $implication = $this->group->getImplication();

        else {
            $internalRootMeta = $this->group->getInternalRootMetadata();
            $implication[$internalRootMeta->getId()] = [

                // no need "source" entry here
                // just extend tree for extension order
                "implication" => $this->group->getImplication()
            ];
        }

        // flat implication to sorted ids
        $this->initIds($implication);
        $mappings = [];

        // extend new state
        if ($this->group->hasDownloadable()) {
            $this->log->info("extend packages");

            $this->externalMetas = $this->group->getExternalMetas();
            $this->packagesDir = $this->directory->getPackagesDir();
            $metadata = $this->group->getInternalRootMetadata();

            // implication recursive root else
            // recycle current
            if ($metadata->getCategory() != InternalMetaCategory::OBSOLETE) {
                $id = $metadata->getId();

                foreach ($metadata->getStructureMappings() as $dir => $mapping)
                    $mappings[$mapping][$id] = $metadata->getDir() .
                        $dir;

                $separators = substr_count($this->directory->getRootDir() .
                    $metadata->getDir() .
                    $metadata->getStatefulPath(), '/');

                $this->structures[$id] = [
                    "dir" => "$this->packagesDir/$id",
                    "separators" => $separators,
                    "state" => $metadata->getStatefulPath(),
                    "extendables" => $metadata->getExtendablePaths()
                ];
            }

            foreach ($this->externalMetas as $id => $metadata) {
                if ($metadata->getDir() == "" &&
                    $metadata->getCategory() == ExternalMetaCategory::REDUNDANT)
                    continue; // ignore recycled root

                $this->log->info(
                    $this->box->get(Content::class,
                        content: $metadata->getContent()));

                foreach ($metadata->getStructureMappings() as $dir => $mapping)
                    $mappings[$mapping][$id] = $metadata->getDir() .
                        $dir;

                $separators = substr_count($this->directory->getRootDir() .
                    $metadata->getDir() .
                    $metadata->getStatefulPath(), '/');

                $this->structures[$id] = [
                    "dir" => "$this->packagesDir/$id",
                    "separators" => $separators,
                    "state" => $metadata->getStatefulPath(),
                    "extendables" => $metadata->getExtendablePaths()
                ];
            }

        // refresh
        } else {
            $this->log->info("refresh cached extension files");

            foreach ($this->group->getInternalMetas() as $id => $metadata)
                if ($metadata->getCategory() != InternalMetaCategory::OBSOLETE) {
                    $this->log->info(
                        $this->box->get(Content::class,
                            content: $metadata->getContent()));

                    foreach ($metadata->getStructureMappings() as $dir => $mapping)
                        $mappings[$mapping][$id] = $metadata->getDir() .
                            $dir;

                    $separators = substr_count($metadata->getSource() .
                        $metadata->getStatefulPath(), '/');

                    $this->structures[$id] = [
                        "dir" => $metadata->getSource(),
                        "separators" => $separators,
                        "state" => $metadata->getStatefulPath(),
                        "extendables" => $metadata->getExtendablePaths()
                    ];
                }
        }

        $rootSeparators = substr_count($this->directory->getRootDir(), '/');

        // filter dirs and
        // create extension files
        foreach ($this->structures as $id_ => $structure) {
            $dir = $structure["dir"];
            $state = $dir . $structure["state"];
            $dynamicRoot = "dirname(__DIR__, " . $structure["separators"] - $rootSeparators . ")";
            $content = "";

            // mapping indicator
            foreach ($structure["extendables"] as $extendable) {
                $content .= "\n\t\"$extendable\" => [";
                $dirs = $mappings[":$id_$extendable"] ??
                    [];

                // loop by implication index to keep order
                foreach ($this->indexes as $index => $id)
                    foreach ($dirs as $identifier => $directory)
                        if ($identifier == $id)
                            $content .= "\n\t\t$index => $dynamicRoot . \"$directory\",";

                $content .= "\n\t],";
            }

            $this->directory->createDir($state);

            if (false === $this->file->put("$state/extensions.php",
                "<?php return [$content\n];"))
                throw new Error(
                    "Cant write file '$state/extensions.php'."
                );
        }
    }

    /**
     * Flat tree - all sorted ids.
     *
     * @param array $tree
     */
    private function initIds(array $tree): void
    {
        foreach ($tree as $id => $subtree) {
            $this->initIds($subtree["implication"]);

            $this->indexes[] = $id;
        }
    }
}