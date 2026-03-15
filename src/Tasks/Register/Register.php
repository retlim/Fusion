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

namespace Valvoid\Fusion\Tasks\Register;

use Valvoid\Box\Box;
use Valvoid\Fusion\Dir\Dir as Directory;
use Valvoid\Fusion\Log\Events\Errors\Error as InternalError;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Metadata\External\Category as ExternalMetaCategory;
use Valvoid\Fusion\Metadata\Internal\Category as InternalMetaCategory;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tasks\Task;
use Valvoid\Fusion\Wrappers\File;

/**
 * Register task to create common autoloader.
 */
class Register extends Task
{
    /** @var array Namespace prefixes to path. */
    private array $prefixes = [];

    /** @var array ASAP script files. */
    private array $asap = [];

    /** @var array Individual package state dirs. */
    private array $states = [];

    /**
     * Constructs the task.
     *
     * @param Box $box Dependency injection container.
     * @param Group $group Tasks group.
     * @param Directory $directory Current working directory.
     * @param Log $log Event log.
     * @param File $file Standard file logic wrapper.
     * @param array $config Task config.
     */
    public function __construct(
        private readonly Box $box,
        private readonly Group $group,
        private readonly Directory $directory,
        private readonly Log $log,
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

    /**
     * Register external new state.
     */
    private function registerNewState(): void
    {
        $this->log->info("register loadable, recyclable, and movable packages");

        $packages = $this->directory->getPackagesDir();

        foreach ($this->group->getExternalMetas() as $id => $metadata)
            if ($metadata->getCategory() == ExternalMetaCategory::DOWNLOADABLE) {
                $this->log->info(
                    $this->box->get(Content::class,
                        content: $metadata->getContent()));

                $state = "$packages/$id" . $metadata->getStatefulPath();
                $this->states[$id] = $state;

                $this->collectInflatedCode($state, $metadata->getDir());
            }

        foreach ($this->group->getInternalMetas() as $id => $metadata)
            if ($metadata->getCategory() != InternalMetaCategory::OBSOLETE) {
                $this->log->info(
                    $this->box->get(Content::class,
                        content: $metadata->getContent()));

                $state = "$packages/$id" . $metadata->getStatefulPath();
                $this->states[$id] = $state;

                $this->collectInflatedCode($state, $metadata->getDir());
            }

        $rootMetadata = $this->group->getExternalRootMetadata() ??
            $this->group->getInternalRootMetadata();

        $path = $rootMetadata->getStatefulPath();

        $this->writeAutoloader(
            "$packages/" . $rootMetadata->getId() . $path,
            $path
        );

        $this->writePrefixes();
    }

    /**
     * Register internal state.
     */
    private function registerCurrentState(): void
    {
        $this->log->info("register internal packages");

        foreach ($this->group->getInternalMetas() as $id => $metadata) {
            if ($metadata->getCategory() == InternalMetaCategory::OBSOLETE)
                continue;

            $this->log->info(
                $this->box->get(Content::class,
                    content: $metadata->getContent()));

            $state = $metadata->getSource() . $metadata->getStatefulPath();
            $this->states[$id] = $state;

            $this->collectInflatedCode($state, $metadata->getDir());
        }

        $this->writeAutoloader(
            $this->directory->getStatefulDir(),
            $this->group->getInternalRootMetadata()->getStatefulPath()
        );

        $this->writePrefixes();
    }

    /**
     * Writes prefix to each package.
     * @throws InternalError
     */
    private function writePrefixes(): void
    {
        $content = "";

        // parse to readable code
        foreach ($this->prefixes as $prefix => $path)
            $content .= "\n\t'$prefix' => '$path',";

        foreach ($this->states as $dir)
            if (false === $this->file->put(
                "$dir/prefixes.php",
                "<?php\n" .
                "return [$content\n];"))
                throw new InternalError(
                    "Cant write '$dir/prefixes.php'."
                );
    }

    /**
     * Collects injectable autoloader code.
     *
     * @param string $dir Inflated code.
     * @param string $path Relative injection path.
     */
    private function collectInflatedCode(string $dir, string $path): void
    {
        $file = "$dir/lazy.php";

        if ($this->file->exists($file)) {
            $map = $this->file->require($file);

            foreach ($map as $loadable => $file) {

                // leading slash
                // .php extension
                $file = substr($file, 1);
                $file = substr($file, 0, -4);
                $file = explode('/', $file);
                $file = array_reverse($file);

                $loadable = explode("\\", $loadable);
                $loadable = array_reverse($loadable);

                foreach ($loadable as $i => $segment) {
                    if (!isset($file[$i]) || $segment != $file[$i])
                        break;

                    unset($loadable[$i]);
                    unset($file[$i]);
                }

                $loadable = array_reverse($loadable);
                $loadable = implode('\\', $loadable);
                $file = array_reverse($file);
                $file = implode('/', $file);

                // prevent trailing slash
                $this->prefixes[$loadable] = ($file != "") ?
                    "$path/$file" :
                    $path;
            }
        }

        $file = "$dir/asap.php";

        if ($this->file->exists($file)) {
            $list = $this->file->require($file);

            foreach ($list as $file)
                $this->asap[] = $path . $file;
        }
    }

    /**
     * Writes ASAP and prefixed autoloader to internal or external
     * state cache directory.
     *
     * @param string $dir Directory.
     * @param string $path Path.
     * @throws InternalError
     */
    private function writeAutoloader(string $dir, string $path): void
    {
        $this->directory->createDir($dir);

        // sort key list
        // longer prefix first
        krsort($this->prefixes, SORT_STRING);

        $depth = substr_count($path, '/');
        $autoloader = $this->file->get(__DIR__ . "/Autoloader.php");

        if ($autoloader === false)
            throw new InternalError(
                "Cant read the snapshot file \"" .
                __DIR__ . "/Autoloader.php\"."
            );

        $autoloader = str_replace(
            ", 2)",
            ", $depth)",
            $autoloader
        );

        if ($this->asap) {
            $content = "";

            foreach ($this->asap as $file)
                $content .= "\n\t\t'$file',";

            $autoloader = str_replace(
                "\$asap = []",
                "\$asap = [$content\n\t]",
                $autoloader
            );
        }

        if ($this->prefixes) {
            $content = "";

            foreach ($this->prefixes as $loadable => $file)
                $content .= "\n\t\t'$loadable' => '$file',";

            $autoloader = str_replace(
                "\$prefixes = []",
                "\$prefixes = [$content\n\t]",
                $autoloader
            );
        }

        if (!$this->file->put(
            "$dir/Autoloader.php",
            $autoloader))
            throw new InternalError(
                "Cant write '$dir/Autoloader.php'."
            );
    }
}