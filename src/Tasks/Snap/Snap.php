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

use Valvoid\Box\Box;
use Valvoid\Fusion\Dir\Dir as Directory;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Metadata\External\External as ExternalMetadata;
use Valvoid\Fusion\Metadata\Internal\Internal as InternalMetadata;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tasks\Task;
use Valvoid\Fusion\Util\Pattern\Interpreter as PatternInterpreter;
use Valvoid\Fusion\Util\Version\Parser;
use Valvoid\Fusion\Wrappers\File;

/**
 * Snap task to persist built state.
 */
class Snap extends Task
{
    /** @var array<string, ExternalMetadata> External metas. */
    private array $metas;

    /** @var array Implication. */
    private array $implication;

    /** @var array<string, string> Snapshot file content. */
    private array $content;

    /** @var string Current layer. */
    private string $layer = "local";

    /** @var array Source intersections. */
    private array $intersections = [];

    /**
     * Constructs the task.
     *
     * @param Box $box Dependency injection container.
     * @param Group $group Tasks group.
     * @param Log $log Event log.
     * @param Directory $dir Current working directory.
     * @param File $file Standard file logic wrapper.
     * @param array $config Task config.
     */
    public function __construct(
        private readonly Box $box,
        private readonly Group $group,
        private readonly Log $log,
        private readonly Directory $dir,
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
        $this->log->info("persist versions");

        $this->metas = $this->group->getExternalMetas();
        $this->implication = $this->group->getImplication();
        $metadata = $this->group->getRootMetadata();
        $id = $metadata->getId();

        // refresh/create state file
        // redundant state
        $statefulDir = $this->group->hasDownloadable() ?
            $this->dir->getPackagesDir() . "/$id" . $metadata->getStatefulPath() :
            $this->dir->getStatefulDir();

        // do not cache root
        // only nested dependencies
        if (isset($this->implication[$id]))
            $this->implication = $this->implication[$id]["implication"];

        $this->dir->createDir($statefulDir);

        // internal root only
        // development
        if ($metadata instanceof InternalMetadata) {
            $this->intersections = $metadata->getContent()["intersections"];

            // local development
            $ids = $metadata->getLocalIds();
            $file = "$statefulDir/snapshot.local.json";

            if ($ids === null)
                $this->dir->delete($file);

            else {
                $this->log->info("local:");
                $this->addRootIds($ids, $file);
            }

            // shared development
            $ids = $metadata->getDevelopmentIds();
            $file = "$statefulDir/snapshot.dev.json";
            $this->layer = "development";

            if ($ids === null)
                $this->dir->delete($file);

            else {
                $this->log->info("development:");
                $this->addRootIds($ids, $file);
            }
        }

        $this->layer = "production";

        // common production
        // internal or external
        $this->log->info("production:");
        $this->addRootIds(
            $metadata->getProductionIds(),
            "$statefulDir/snapshot.json"
        );
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
        // already done
        if (isset($this->content[$id]))
            return;

        $metadata = $this->metas[$id];
        $version = $metadata->getVersion();

        // common version valid for sub layers
        if (isset($this->intersections[$id][$this->layer])) {
            $references = $this->getReferences(
                Parser::getInflatedVersion($version),
                $this->intersections[$id][$this->layer]["reference"]);

            if (empty($references)) {
                $filename = ($this->layer == "development") ?
                    "fusion.dev.php" :
                    "fusion.json";

                $file = $this->dir->getRootDir() . "/$filename";

                throw new Error(
                    "Invalid source intersection. The " .
                    "built version '$version' for the package '$id' " .
                    "conflicts with the reference in '$file'. " .
                    "Adjust the source reference so the version " .
                    "can pass this layer. "
                );
            }

            // version overridden by offset fake
            // first pattern to pass
            foreach ($references as $reference) {
                if (isset($reference["offset"]))
                    $version .= ":" . $reference["offset"];

                break;
            }

        // version overridden by offset fake
        // first match is production layer
        } else foreach ($metadata->getLayers() as $layer)
            if (isset($layer["version"]) &&
                $version !== $layer["version"]) {
                $version .= ":" . $metadata->getSource()["reference"];

                break;
            }

        $this->content[$id] = $version;

        $this->log->info(
            $this->box->get(Content::class,
                content: $metadata->getContent()));
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

    /**
     * Returns reference patterns the version passes.
     *
     * @param array $version Version.
     * @param array $reference Patterns.
     * @return array Match.
     */
    private function getReferences(array $version, array $reference): array
    {
        // per round brackets
        $wrapper = [];
        $skip = false;

        foreach ($reference as $entry) {
            if ($entry == "||") {
                $skip = false;
                $intersection = array_intersect_key(...$wrapper);

                if ($intersection)
                    return $intersection;

                // remove last value
                // the "||" value takes it then
                array_pop($wrapper);
                continue;

            // ignore
            // actually default behavior
            // but parsed for easier debugging
            } elseif ($entry == "&&")
                continue;

            // fake result
            elseif ($skip)
                $entry = [];

            // pattern
            // inflated semantic version
            elseif (isset($entry["sign"])) {
                $matches = [];

                if (PatternInterpreter::isMatch($version, $entry)) {
                    $inline = $entry["major"] . "." .
                        $entry["minor"] . "." .
                        $entry["patch"];

                    if ($entry["release"])
                        $inline .= "-" . $entry["release"];

                    if ($entry["build"])
                        $inline .= "+" . $entry["build"];

                    if (isset($entry["offset"]))
                        $inline .= ":" . $entry["offset"];

                    $matches[$inline] = $entry;
                }

                $entry = $matches;

            // brackets
            // nested wrapper
            } else $entry = $this->getReferences($version, $entry);

            // skip all next "&&" entries
            // empty intersection
            $skip = !$entry;
            $wrapper[] = $entry;
        }

        return ($skip || !$wrapper) ? [] :
            array_intersect_key(...$wrapper);
    }
}