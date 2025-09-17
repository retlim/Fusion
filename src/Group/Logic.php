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

namespace Valvoid\Fusion\Group;

use Valvoid\Fusion\Fusion;
use Valvoid\Fusion\Metadata\External\Category;
use Valvoid\Fusion\Metadata\External\External as ExternalMeta;
use Valvoid\Fusion\Metadata\Internal\Internal as InternalMeta;
use Valvoid\Fusion\Util\Metadata\Structure;

/**
 * Default task group implementation.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Logic implements Group
{
    /** @var array<string, InternalMeta> Internal metas by ID. */
    protected array $internalMetas = [];

    /** @var array<string, ExternalMeta> External metas by ID. */
    protected array $externalMetas = [];

    /** @var array Root-leaf metadata relations (inline sources - ID's). */
    protected array $implication = [];

    /** @var bool Loadable external indicator. */
    protected bool $downloadable;

    /** @var InternalMeta Internal root meta. */
    protected InternalMeta $internalRootMeta;

    /** @var ?ExternalMeta Recursive external root meta. */
    protected ?ExternalMeta $externalRootMeta = null;

    /** @var string[] Runtime layer implication breadcrumb. */
    protected array $implicationBreadcrumb = [];

    /**
     * Sets internal metas.
     *
     * @param array<string, InternalMeta> $metas Metas.
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
     * @param array<string, ExternalMeta> $metas Metas.
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
     * @return ExternalMeta|null Meta.
     */
    public function getExternalRootMetadata(): ?ExternalMeta
    {
        return $this->externalRootMeta;
    }

    /**
     * Returns internal root meta.
     *
     * @return InternalMeta Meta.
     */
    public function getInternalRootMetadata(): InternalMeta
    {
        return $this->internalRootMeta;
    }

    /**
     * Returns root metadata.
     *
     * @return ExternalMeta|InternalMeta Meta.
     */
    public function getRootMetadata(): ExternalMeta|InternalMeta
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
     * @return array<string, ExternalMeta> Metas.
     */
    public function getExternalMetas(): array
    {
        return $this->externalMetas;
    }

    /**
     * Returns internal metas.
     *
     * @return array<string, InternalMeta> Metas.
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
     * Returns event path.
     *
     * @param string $source Source.
     * @return array Path.
     */
    public function getPath(string $source): array
    {
        $sourcePath = $this->getSourcePath($this->implication, $source);
        $path = [];

        if ($this->implicationBreadcrumb) {
            $id = array_key_first($sourcePath);

            // remove recursive root
            if ($id) {
                $source = array_shift($sourcePath);
                $metadata = $this->externalMetas[$id];
            }

            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

            // reverse
            // take first match
            foreach (array_reverse($backtrace) as $entry)
                if ($entry["class"] == Fusion::class) {
                    $path[] = [
                        "layer" => $entry["line"] . " - " . $entry["file"] . " (runtime config layer)",
                        "breadcrumb" => $this->implicationBreadcrumb,
                        "source" => $source
                    ];

                    break;
                }

        } else
            $metadata = $this->internalRootMeta;

        foreach ($sourcePath as $id => $source) {
            if (isset($metadata))
                foreach ($metadata->getLayers() as $layer => $content)
                    if (isset($content["structure"])) {
                        $breadcrumb = Structure::getBreadcrumb(
                            $content["structure"],
                            $source,
                            ["structure"]
                        );

                        if ($breadcrumb) {
                            $path[] = [
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

        return $path;
    }

    /**
     * Returns first match path to a source.
     *
     * @param array $implication Implication.
     * @param string $source Source.
     * @return array Path.
     */
    public function getSourcePath(array $implication, string $source): array
    {
        $path = [];

        foreach ($implication as $identifier => $entry) {
            if ($source == $entry["source"])
                return [
                    $identifier => $entry["source"]
                ];

            $path = $this->getSourcePath($entry["implication"], $source);

            if ($path)
                return [
                    $identifier => $entry["source"],
                    ...$path
                ];
        }

        return $path;
    }
}