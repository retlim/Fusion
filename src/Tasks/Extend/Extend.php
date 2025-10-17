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
use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Dir\Proxy as DirProxy;
use Valvoid\Fusion\Group\Group as GroupProxy;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Proxy as LogProxy;
use Valvoid\Fusion\Metadata\External\External as ExternalMeta;
use Valvoid\Fusion\Metadata\Internal\Category as InternalMetaCategory;
use Valvoid\Fusion\Metadata\External\Category as ExternalMetaCategory;
use Valvoid\Fusion\Metadata\Metadata;
use Valvoid\Fusion\Tasks\Task;
use Valvoid\Fusion\Wrappers\Dir;
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

    /** @var array Parents per package id. */
    private array $filters = [];

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
        $this->initFilters($implication, []);
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

                foreach ($metadata->getStructureMappings() as $dir => $mapping) {
                    $mappings[$mapping][$id] =

                        // absolute dir since deps (extensions.php)
                        // should not be in repo what result in a build
                        $this->directory->getRootDir() .
                        $metadata->getDir() .
                        $dir;
                }

                $this->structures[$id] = [
                    "dir" => "$this->packagesDir/$id",
                    "state" => $metadata->getStructureCache(),
                    "extensions" => $metadata->getStructureExtensions(),
                    "extendables" => $metadata->getExtendablePaths()
                ];

                $this->spread($metadata, $id);
            }

            foreach ($this->externalMetas as $id => $metadata) {
                if ($metadata->getDir() == "" &&
                    $metadata->getCategory() == ExternalMetaCategory::REDUNDANT)
                    continue; // ignore recycled root

                $this->log->info(
                    $this->box->get(Content::class,
                        content: $metadata->getContent()));

                foreach ($metadata->getStructureMappings() as $dir => $mapping)
                    $mappings[$mapping][$id] =

                        // absolute dir since deps (extensions.php)
                        // should not be in repo what result in a build
                        $this->directory->getRootDir() .
                        $metadata->getDir() .
                        $dir;

                $this->structures[$id] = [
                    "dir" => "$this->packagesDir/$id",
                    "state" => $metadata->getStructureCache(),
                    "extensions" => $metadata->getStructureExtensions(),
                    "extendables" => $metadata->getExtendablePaths()
                ];

                $this->spread($metadata, $id);
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
                        $mappings[$mapping][$id] = $this->directory->getRootDir() .
                            $metadata->getDir() .
                            $dir;

                    $this->structures[$id] = [
                        "dir" => $metadata->getSource(),
                        "extensions" => $metadata->getStructureExtensions(),
                        "state" => $metadata->getStructureCache(),
                        "extendables" => $metadata->getExtendablePaths()
                    ];
                }
        }

        // filter dirs and
        // create extension files
        foreach ($this->structures as $id_ => $structure) {
            $filter = $this->filters[$id_];
            $dir = $structure["dir"];
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
                            $content .= "\n\t\t$index => \"$directory\",";

                $content .= "\n\t],";
            }

            // deprecated legacy injection
            foreach ($structure["extensions"] as $extension) {
                $content .= "\n\t\"$extension\" => [";
                $dirs = $mappings[":$id_$extension"] ??
                    [];

                // deprecated injection
                // only existing content
                // prevent redundant checks after
                if ($this->dir->is("$dir$extension")) {
                    $this->filterExtension("$dir$extension", $filter);

                    // add mapping or
                    // shift ordered identifiers
                    foreach ($this->indexes as $index => $id)
                        if (isset($dirs[$id]))
                            $content .= "\n\t\t$index => \"" . $dirs[$id] . "\",";

                        // deprecated injection
                        elseif ($this->dir->is("$dir$extension/$id"))
                            $content .= "\n\t\t$index => \"$id\",";

                // mapping
                //  loop by implication index to keep order
                } else foreach ($this->indexes as $index => $id)
                    foreach ($dirs as $identifier => $directory)
                        if ($identifier == $id)
                            $content .= "\n\t\t$index => \"$directory\",";

                $content .= "\n\t],";
            }

            $state = $dir . $structure["state"];

            $this->directory->createDir($state);

            if (false === $this->file->put(
                "$state/extensions.php",
                "<?php\n" .
                "// Auto-generated by Fusion package manager. \n" .
                "// Do not modify.\n" .
                "return [$content\n];"))
                throw new Error(
                    "Cant write file '$state/extensions.php'."
                );
        }
    }

    /**
     * Filters extensions.
     *
     * @param string $dir Current dir.
     * @param array $filter Valid dirs.
     * @throws Error Internal error.
     */
    private function filterExtension(string $dir, array $filter): void
    {
        // empty
        // inside identifier extension
        if (!$filter)
            return;

        $filenames = $this->dir->getFilenames($dir, SCANDIR_SORT_NONE);

        if ($filenames === false)
            throw new Error(
                "Cant read directory '$dir'."
            );

        foreach ($filenames as $filename) {
            if ($filename == "." || $filename == "..")
                continue;

            $file = "$dir/$filename";

            if ($this->dir->is($file)) {
                if (isset($filter[$filename]))
                    $this->filterExtension("$dir/$filename", $filter[$filename]);

                else $this->directory->delete($file);

            } else $this->directory->delete($file);
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

    /**
     * Spreads injected  extensions.
     *
     * @param Metadata $metadata
     * @param string $id
     * @throws Error Internal error.
     * @deprecated Will be removed in version 2.0.0
     */
    private function spread(Metadata $metadata, string $id): void
    {
        // loadable
        // extend other packages
        foreach ($metadata->getStructureSources() as $path => $source)

            // jump over recursive
            if ($path)
                foreach ($this->externalMetas as $externalId => $externalMeta) {
                    if ($id != $externalId) {
                        $dir = "$this->packagesDir/$id" .

                            // has entry for package
                            "$path/$externalId";

                        foreach ($externalMeta->getStructureExtensions() as $extension) {
                            $from = $dir .

                                // own ID group
                                "$extension/$id";

                            if ($this->dir->is($from)) {
                                $to = "$this->packagesDir/$externalId" .

                                    // own ID group
                                    "$extension/$id";

                                // maybe obsolete collision
                                $this->directory->delete($to);
                                $this->directory->createDir($to);
                                $this->directory->rename($from, $to);

                                // storage wrapper
                                // clear empty dir prefixes
                                $this->directory->clear(
                                    "$this->packagesDir/$id$path",
                                    "/$externalId$extension/$id"
                                );
                            }
                        }
                    }
                }
    }

    /**
     * Get parents of package index. truncate
     * Get structure filter.
     *
     * @param array $tree
     * @param array $filter
     */
    private function initFilters(array $tree, array $filter): void
    {
        foreach ($tree as $id => $subtree) {

            // handle multiple parent
            // package can be a dependency of multiple packages
            // init if not yet
            $this->filters[$id] ??= [];
            $this->filters[$id] = array_merge_recursive($this->filters[$id], $filter);
            $this->filters[$id] = array_merge_recursive($this->filters[$id],

                // create assoc array from id and
                // add it as recursive
                $this->getAssoc(explode('/', $id)));

            $this->initFilters($subtree["implication"], $this->filters[$id]);
        }
    }

    /**
     * Returns assoc array.
     *
     * @param array $breadcrumb
     * @return array
     */
    private function getAssoc(array $breadcrumb): array
    {
        $result = [];
        $key = array_shift($breadcrumb);

        if ($key)
            $result[$key] = $this->getAssoc($breadcrumb);

        return $result;
    }
}