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

namespace Valvoid\Fusion\Tasks\Register;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Dir\Proxy as DirProxy;
use Valvoid\Fusion\Group\Group as GroupProxy;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Errors\Error as InternalError;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Proxy as LogProxy;
use Valvoid\Fusion\Metadata\External\Category as ExternalMetaCategory;
use Valvoid\Fusion\Metadata\Internal\Category as InternalMetaCategory;
use Valvoid\Fusion\Tasks\Task;
use Valvoid\Fusion\Wrappers\File;

/**
 * Register task to create common autoloader.
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class Register extends Task
{
    /**
     * Constructs the task.
     *
     * @param Box $box Dependency injection container.
     * @param GroupProxy $group Tasks group.
     * @param DirProxy $directory Current working directory.
     * @param LogProxy $log Event log.
     * @param File $file Standard file logic wrapper.
     * @param array $config Task config.
     */
    public function __construct(
        private readonly Box $box,
        private readonly GroupProxy $group,
        private readonly DirProxy $directory,
        private readonly LogProxy $log,
        private readonly File $file,
        array $config)
    {
        parent::__construct($config);
    }

    /** Executes the task. */
    public function execute(): void
    {
        // optional
        // register new state
        $this->group->hasDownloadable() ?
            $this->registerNewState() :
            $this->registerCurrentState();
    }

    /** Register external new state. */
    private function registerNewState(): void
    {
        $this->log->info("register loadable, recyclable, and movable packages");

        $packagesDir = $this->directory->getPackagesDir();
        $lazy = $asap = [];

        foreach ($this->group->getExternalMetas() as $id => $meta)
            if ($meta->getCategory() == ExternalMetaCategory::DOWNLOADABLE) {
                $this->log->info(
                    $this->box->get(Content::class,
                        content: $meta->getContent()));

                $this->appendInflated($lazy, $asap,

                    // absolute loadable direction
                    // internal state
                    "$packagesDir/$id" . $meta->getStructureCache() . "/loadable",

                    // relative path
                    $meta->getDir()
                );
            }

        foreach ($this->group->getInternalMetas() as $id => $meta)
            if ($meta->getCategory() != InternalMetaCategory::OBSOLETE) {
                $this->log->info(
                    $this->box->get(Content::class,
                        content: $meta->getContent()));

                $this->appendInflated($lazy, $asap,

                    // absolute loadable direction
                    // internal state
                    "$packagesDir/$id" . $meta->getStructureCache() . "/loadable",

                    // relative path
                    $meta->getDir()
                );
            }

        $rootMeta = $this->group->getExternalRootMetadata() ??
            $this->group->getInternalRootMetadata();

        $path = $rootMeta->getStructureCache();

        $this->writeAutoloader($this->directory->getPackagesDir() . "/" .
            $rootMeta->getId() . $path, $path, $asap, $lazy);
    }

    /** Register internal state. */
    private function registerCurrentState(): void
    {
        $this->log->info("register internal packages");

        $lazy = $asap = [];

        foreach ($this->group->getInternalMetas() as $meta) {
            if ($meta->getCategory() == InternalMetaCategory::OBSOLETE)
                continue;

            $this->log->info(
                $this->box->get(Content::class,
                    content: $meta->getContent()));

            $this->appendInflated($lazy, $asap,

                // absolute loadable direction
                // internal state
                $meta->getSource() . $meta->getStructureCache() . "/loadable",

                // relative path
                $meta->getDir()
            );
        }

        $this->writeAutoloader($this->directory->getCacheDir(),

            // cache path
            $this->group->getInternalRootMetadata()->getStructureCache(),
            $asap, $lazy
        );
    }

    /**
     * Requires inflated code.
     *
     * @param array $lazy Lazy.
     * @param array $asap ASAP.
     * @param string $dir Dir.
     * @param string $path Path.
     */
    private function appendInflated(array  &$lazy, array &$asap, string $dir,
                                    string $path): void
    {
        $file = "$dir/lazy.php";

        if ($this->file->exists($file)) {
            $map = $this->file->require($file);

            foreach ($map as $loadable => $file)
                $lazy[$loadable] = $path . $file;
        }

        $file = "$dir/asap.php";

        if ($this->file->exists($file)) {
            $list = $this->file->require($file);

            foreach ($list as $file)
                $asap[] = $path . $file;
        }
    }


    /**
     * Writes ASAP and lazy loadable autoloader to internal or external
     * state cache directory.
     *
     * @param string $dir Directory.
     * @param string $path Path.
     * @param array $asap ASAP.
     * @param array $lazy Path.
     * @throws Error
     */
    private function writeAutoloader(string $dir, string $path, array $asap,
                                     array $lazy): void
    {
        $this->directory->createDir($dir);

        // sort key list
        ksort($lazy, SORT_STRING);

        $depth = substr_count($path, '/');
        $autoloader = $this->file->get(__DIR__ . "/Autoloader.php");

        if ($autoloader === false)
            throw new InternalError(
                "Can't read the snapshot file \"" .
                __DIR__ . "/Autoloader.php\"."
            );

        $autoloader = str_replace(
            ", 2)",
            ", $depth)",
            $autoloader
        );

        if ($asap) {
            $content = "";

            foreach ($asap as $file)
                $content .= "\n\t\t'$file',";

            $autoloader = str_replace(
                "ASAP = []",
                "ASAP = [$content\n\t]",
                $autoloader
            );
        }

        if ($lazy) {
            $content = "";

            foreach ($lazy as $loadable => $file)
                $content .= "\n\t\t'$loadable' => '$file',";

            $autoloader = str_replace(
                "LAZY = []",
                "LAZY = [$content\n\t]",
                $autoloader
            );
        }

        if (!$this->file->put("$dir/Autoloader.php", $autoloader))
            throw new Error(
                "Can't write to the file \"$dir/Autoloader.php\"."
            );
    }
}