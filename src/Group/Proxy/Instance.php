<?php
/**
 * Fusion. A package manager for PHP-based projects.
 * Copyright Valvoid
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Valvoid\Fusion\Group\Proxy;

use Valvoid\Fusion\Metadata\External\External as ExternalMeta;
use Valvoid\Fusion\Metadata\Internal\Internal as InternalMeta;

/**
 * Default task group proxy instance.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Instance implements Proxy
{
    /** @var Proxy Implementation. */
    protected Proxy $logic;

    /**
     * Constructs the task group.
     *
     *  @param Proxy|Logic $logic Any or default logic implementation.
     */
    public function __construct(Proxy|Logic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * Sets internal metas.
     *
     * @param array<string, InternalMeta> $metas Metas.
     */
    public function setInternalMetas(array $metas): void
    {
        $this->logic->setInternalMetas($metas);
    }

    /**
     * Sets implication.
     *
     * @param array $implication Implication.
     */
    public function setImplication(array $implication): void
    {
        $this->logic->setImplication($implication);
    }

    /**
     * Sets external metas.
     *
     * @param array<string, ExternalMeta> $metas Metas.
     */
    public function setExternalMetas(array $metas): void
    {
        $this->logic->setExternalMetas($metas);
    }

    /**
     * Returns optional external root meta.
     *
     * @return ExternalMeta|null Meta.
     */
    public function getExternalRootMetadata(): ?ExternalMeta
    {
        return $this->logic->getExternalRootMetadata();
    }

    /**
     * Returns internal root meta.
     *
     * @return InternalMeta Meta.
     */
    public function getInternalRootMetadata(): InternalMeta
    {
        return $this->logic->getInternalRootMetadata();
    }

    /**
     * Returns root metadata.
     *
     * @return ExternalMeta|InternalMeta Meta.
     */
    public function getRootMetadata(): ExternalMeta|InternalMeta
    {
        return $this->logic->getRootMetadata();
    }

    /**
     * Returns indicator for loadable meta.
     *
     * @return bool Indicator.
     */
    public function hasDownloadable(): bool
    {
        return $this->logic->hasDownloadable();
    }

    /**
     * Returns external metas.
     *
     * @return array<string, ExternalMeta> Metas.
     */
    public function getExternalMetas(): array
    {
        return $this->logic->getExternalMetas();
    }

    /**
     * Returns internal metas.
     *
     * @return array<string, InternalMeta> Metas.
     */
    public function getInternalMetas(): array
    {
        return $this->logic->getInternalMetas();
    }

    /**
     * Sets implication breadcrumb. If set implication starts
     * at runtime layer passed to the Fusion object.
     *
     * @param string[] $breadcrumb Breadcrumb.
     */
    public function setImplicationBreadcrumb(array $breadcrumb): void
    {
        $this->logic->setImplicationBreadcrumb($breadcrumb);
    }

    /**
     * Returns implication.
     *
     * @return array Implication.
     */
    public function getImplication(): array
    {
        return $this->logic->getImplication();
    }

    /**
     * Returns event path.
     *
     * @param string $source Source.
     * @return array Path.
     */
    public function getPath(string $source): array
    {
        return $this->logic->getPath($source);
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
        return $this->logic->getSourcePath($implication, $source);
    }
}