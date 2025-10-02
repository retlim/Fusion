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

namespace Valvoid\Fusion\Tasks\Image;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Config\Config;
use Valvoid\Fusion\Group\Group;
use Valvoid\Fusion\Log\Events\Errors\Error as InternalError;
use Valvoid\Fusion\Log\Events\Errors\Metadata as MetaError;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Proxy as LogProxy;
use Valvoid\Fusion\Metadata\Internal\Builder as InternalMetadataBuilder;
use Valvoid\Fusion\Metadata\Internal\Internal as InternalMetadata;
use Valvoid\Fusion\Tasks\Task;
use Valvoid\Fusion\Wrappers\Dir;
use Valvoid\Fusion\Wrappers\File;

/**
 * Image task to get internal metas.
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class Image extends Task
{
    /** @var array<string, InternalMetadata> Internal metas by ID. */
    private array $metas = [];

    /**
     * Constructs the task.
     *
     * @param Box $box Dependency injection container.
     * @param LogProxy $log Event log.
     * @param File $file Standard file logic wrapper.
     * @param Dir $dir Standard dir logic wrapper.
     * @param array $config Task config.
     */
    public function __construct(
        private readonly Box $box,
        private readonly LogProxy $log,
        private readonly File $file,
        private readonly Dir $dir,
        array $config)
    {
        parent::__construct($config);
    }

    /**
     * Executes the task.
     *
     * @throws MetaError Invalid meta exception.
     * @throws InternalError Internal exception.
     */
    public function execute(): void
    {
        $this->log->info("image internal metas");

        $root = $this->box->get(Config::class)
            ::get("dir", "path");

        // optional
        // internal packages may not exist yet
        // in case of built or replicate from remote source
        if (!$this->file->exists("$root/fusion.json"))
            return;

        // root meta
        // at project package dir
        // without path
        $metadata = $this->getMetadata($root, "");
        $this->metas[$metadata->getId()] = $metadata;

        $this->log->info(
            $this->box->get(Content::class,
                content: $metadata->getContent()));

        // root structure
        // extract paths
        // jump over before imaged root meta and
        // cache folder paths
        foreach ($metadata->getStructureSources() as $path => $source)

            // empty
            // jump over recursive root
            if ($path) {
                $dir = $root . $path;

                // existing dir
                // root package may in development state
                // with untracked structure paths
                // that exists after build
                if ($this->dir->is($dir))
                    $this->extractMetadata($dir, $path);
            }

        if (isset($this->config["group"]))
            $this->box->get(Group::class)
                ->setInternalMetas($this->metas);
    }

    /**
     * Extracts internal metas from root structure directories.
     *
     * @param string $dir Directory.
     * @throws MetaError Invalid meta exception.
     * @throws InternalError Internal exception.
     */
    private function extractMetadata(string $dir, string $path): void
    {
        if ($this->file->exists("$dir/fusion.json")) {
            $metadata = $this->getMetadata($dir, $path);
            $this->metas[$metadata->getId()] = $metadata;

            $this->log->info(
                $this->box->get(Content::class,
                    content: $metadata->getContent()));

        } else {
            $filenames = $this->dir->getFilenames($dir, SCANDIR_SORT_NONE);

            if ($filenames === false)
                throw $this->box->get(InternalError::class,
                    message: "Can't get filenames from dir '$dir'.");

            foreach ($filenames as $filename)
                if ($filename != "." && $filename != "..") {
                    $file = "$dir/$filename";

                    if ($this->dir->is($file))
                        $this->extractMetadata($file, $path);
                }
        }
    }

    /**
     * Returns internal meta.
     *
     * @param string $dir Absolute.
     * @param string $path Relative.
     * @return InternalMetadata Meta.
     * @throws MetaError Invalid meta exception.
     * @throws InternalError Internal exception.
     */
    private function getMetadata(string $dir, string $path): InternalMetadata
    {
        $file = "$dir/fusion.json";
        $content = $this->file->get($file);
        $builder = $this->box->get(InternalMetadataBuilder::class,
            source: $dir,
            dir: $path);

        if ($content === false)
            throw $this->box->get(InternalError::class,
                message: "Can't get contents from the '$file' file.");

        $builder->addProductionLayer($content, $file);

        // lock nested dev feature
        // root package only for now
        if (!$path) {
            $file = "$dir/fusion.local.php";

            if ($this->file->exists($file))
                $builder->addLocalLayer($this->getLayerContent($file), $file);

            $file = "$dir/fusion.dev.php";

            if ($this->file->exists($file))
                $builder->addDevelopmentLayer($this->getLayerContent($file), $file);
        }

        // auto-generated content
        $file = "$dir/fusion.bot.php";

        if ($this->file->exists($file))
            $builder->addBotLayer($this->getLayerContent($file), $file);

        return $builder->getMetadata();
    }

    /**
     * Returns metadata layer content.
     *
     * @param string $file File.
     * @return array Content.
     * @throws InternalError Internal error.
     */
    private function getLayerContent(string $file): array
    {
        $content = $this->file->require($file);

        if ($content === false)
            throw $this->box->get(InternalError::class,
                message: "Can't get contents from the '$file' file.");

        return $content;
    }
}