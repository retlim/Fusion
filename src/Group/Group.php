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

use Valvoid\Fusion\Metadata\External\External as ExternalMeta;
use Valvoid\Fusion\Metadata\Internal\Internal as InternalMeta;

/**
 * Task group.
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
interface Group
{
    /**
     * Sets internal metas.
     *
     * @param array<string, InternalMeta> $metas Metas.
     */
    public function setInternalMetas(array $metas): void;

    /**
     * Sets implication.
     *
     * @param array $implication Implication.
     */
    public function setImplication(array $implication): void;

    /**
     * Sets external metas.
     *
     * @param array<string, ExternalMeta> $metas Metas.
     */
    public function setExternalMetas(array $metas): void;

    /**
     * Returns optional external root meta.
     *
     * @return ExternalMeta|null Meta.
     */
    public function getExternalRootMetadata(): ?ExternalMeta;

    /**
     * Returns internal root meta.
     *
     * @return InternalMeta Meta.
     */
    public function getInternalRootMetadata(): InternalMeta;

    /**
     * Returns root metadata.
     *
     * @return ExternalMeta|InternalMeta Meta.
     */
    public function getRootMetadata(): ExternalMeta|InternalMeta;

    /**
     * Returns indicator for loadable meta.
     *
     * @return bool Indicator.
     */
    public function hasDownloadable(): bool;

    /**
     * Returns external metas.
     *
     * @return array<string, ExternalMeta> Metas.
     */
    public function getExternalMetas(): array;

    /**
     * Returns internal metas.
     *
     * @return array<string, InternalMeta> Metas.
     */
    public function getInternalMetas(): array;

    /**
     * Sets implication breadcrumb. If set implication starts
     * at runtime layer passed to the Fusion object.
     *
     * @param string[] $breadcrumb Breadcrumb.
     */
    public function setImplicationBreadcrumb(array $breadcrumb): void;

    /**
     * Returns implication.
     *
     * @return array Implication.
     */
    public function getImplication(): array;

    /**
     * Returns event path.
     *
     * @param string $source Source.
     * @return array Path.
     */
    public function getPath(string $source): array;

    /**
     * Returns first match path to a source.
     *
     * @param array $implication Implication.
     * @param string $source Source.
     * @return array Path.
     */
    public function getSourcePath(array $implication, string $source): array;
}