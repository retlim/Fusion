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

namespace Valvoid\Fusion\Tasks;

use Valvoid\Box\Box;
use Valvoid\Fusion\Fusion;
use Valvoid\Fusion\Metadata\External\Category;
use Valvoid\Fusion\Metadata\External\External as ExternalMetadata;
use Valvoid\Fusion\Metadata\Internal\Internal as InternalMetadata;
use Valvoid\Fusion\Util\Metadata\Structure;

/**
 * Common progress between tasks.
 */
class Group
{
    /** @var array<string, InternalMetadata> Internal metas by ID. */
    protected array $internalMetas = [];

    /** @var array<string, ExternalMetadata> External metas by ID. */
    protected array $externalMetas = [];

    /** @var array Root-leaf metadata relations (inline sources - ID's). */
    protected array $implication = [];

    /** @var bool Loadable external indicator. */
    protected bool $downloadable;

    /** @var InternalMetadata Internal root meta. */
    protected InternalMetadata $internalRootMeta;

    /** @var ?ExternalMetadata Recursive external root meta. */
    protected ?ExternalMetadata $externalRootMeta = null;

    /** @var string[] Runtime layer implication breadcrumb. */
    protected array $implicationBreadcrumb = [];

    /**
     * Sets internal metas.
     *
     * @param array<string, InternalMetadata> $metas Metas.
     */
    public function setInternalMetas(array $metas): void
    {
        $this->internalMetas = $metas;

        foreach ($metas as $meta)
            if (!$meta->getDir()) {
                $this->internalRootMeta = $meta;

                break;
            }
    }

    /**
     * Sets implication.
     *
     * @param array $implication Implication.
     */
    public function setImplication(array $implication): void
    {
        $this->implication = $implication;
    }

    /**
     * Sets external metas.
     *
     * @param array<string, ExternalMetadata> $metas Metas.
     */
    public function setExternalMetas(array $metas): void
    {
        $this->externalMetas = $metas;
        $this->externalRootMeta = null;

        foreach ($metas as $meta)
            if (!$meta->getDir())
                $this->externalRootMeta = $meta;

        unset($this->downloadable);
    }

    /**
     * Returns optional external root meta.
     *
     * @return ExternalMetadata|null Meta.
     */
    public function getExternalRootMetadata(): ?ExternalMetadata
    {
        return $this->externalRootMeta;
    }

    /**
     * Returns internal root meta.
     *
     * @return InternalMetadata Meta.
     */
    public function getInternalRootMetadata(): InternalMetadata
    {
        return $this->internalRootMeta;
    }

    /**
     * Returns root metadata.
     *
     * @return ExternalMetadata|InternalMetadata Meta.
     */
    public function getRootMetadata(): ExternalMetadata|InternalMetadata
    {
        return $this->externalRootMeta ??
            $this->internalRootMeta;
    }

    /**
     * Returns indicator for loadable meta.
     *
     * @return bool Indicator.
     */
    public function hasDownloadable(): bool
    {
        if (!isset($this->downloadable)) {
            $this->downloadable = false;

            foreach ($this->externalMetas as $meta)
                if ($meta->getCategory() == Category::DOWNLOADABLE) {
                    $this->downloadable = true;

                    return true;
                }
        }

        return $this->downloadable;
    }

    /**
     * Returns external metas.
     *
     * @return array<string, ExternalMetadata> Metas.
     */
    public function getExternalMetas(): array
    {
        return $this->externalMetas;
    }

    /**
     * Returns internal metas.
     *
     * @return array<string, InternalMetadata> Metas.
     */
    public function getInternalMetas(): array
    {
        return $this->internalMetas;
    }

    /**
     * Sets implication breadcrumb. If set implication starts
     * at runtime layer passed to the Fusion object.
     *
     * @param string[] $breadcrumb Breadcrumb.
     */
    public function setImplicationBreadcrumb(array $breadcrumb): void
    {
        $this->implicationBreadcrumb = $breadcrumb;
    }

    /**
     * Returns implication.
     *
     * @return array Implication.
     */
    public function getImplication(): array
    {
        return $this->implication;
    }

    /**
     * Returns a trace for the first match of the source inside
     * the implication.
     *
     * @param string $source Inline package source.
     * @return array<string, string> Trace.
     */
    public function getEventTrace(string $source): array
    {
        return $this->getPath($source);
    }

    /**
     * Returns event path.
     *
     * @param string $source Source.
     * @return array Path.
     * @deprecated Will be renamed to {@see getEventTrace} in
     * version 3.0.0. The term "path" is confusing here, as it
     * * is already used in the metadata structure and overlaps
     * * with implication semantics.
     */
    public function getPath(string $source): array
    {
        $sourceTrace = $this->getSourceTrace($this->implication, $source);
        $eventTrace = [];

        if ($this->implicationBreadcrumb) {
            $id = array_key_first($sourceTrace);

            // remove recursive root
            if ($id) {
                $source = array_shift($sourceTrace);
                $metadata = $this->externalMetas[$id];
            }

            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

            // reverse
            // take first match
            foreach (array_reverse($backtrace) as $entry)
                if ($entry["class"] == Fusion::class) {
                    $eventTrace[] = [
                        "layer" => $entry["line"] . " - " . $entry["file"] . " (runtime config layer)",
                        "breadcrumb" => $this->implicationBreadcrumb,
                        "source" => $source
                    ];

                    break;
                }

        } else
            $metadata = $this->internalRootMeta;

        foreach ($sourceTrace as $id => $source) {
            if (isset($metadata))
                foreach ($metadata->getLayers() as $layer => $content)
                    if (isset($content["structure"])) {
                        $breadcrumb = Structure::getBreadcrumb(
                            $content["structure"],
                            $source,
                            ["structure"]
                        );

                        if ($breadcrumb) {
                            $eventTrace[] = [
                                "layer" => $layer,
                                "breadcrumb" => $breadcrumb,
                                "source" => $source
                            ];

                            // take first match
                            break;
                        }
                    }

            // next parent
            // last own entry - maybe not built yet
            if (!isset($this->externalMetas[$id]))
                break;

            $metadata = $this->externalMetas[$id];
        }

        return $eventTrace;
    }

    /**
     * Returns a trace for the first match of the source within the
     * implication. The trace is a flat associative array with package
     * identifier keys mapped to package source values, ordered
     * hierarchically from top to bottom.
     *
     * @param array $implication Built dependency graph.
     * @param string $source Inline package source.
     * @return array<string, string> Trace, or an empty array if no
     * match is found.
     */
    public function getSourceTrace(array $implication, string $source): array
    {
        return $this->getSourcePath($implication, $source);
    }

    /**
     * Returns first match path to a source.
     *
     * @param array $implication Implication.
     * @param string $source Source.
     * @return array Path.
     * @deprecated Will be renamed to {@see getSourceTrace} in
     * version 3.0.0. The term "path" is confusing here, as it
     * is already used in the metadata structure and overlaps
     * with implication semantics.
     */
    public function getSourcePath(array $implication, string $source): array
    {
        $trace = [];

        foreach ($implication as $identifier => $entry) {
            if ($source == $entry["source"])
                return [
                    $identifier => $entry["source"]
                ];

            $trace = $this->getSourceTrace($entry["implication"], $source);

            if ($trace)
                return [
                    $identifier => $entry["source"],
                    ...$trace
                ];
        }

        return $trace;
    }
}