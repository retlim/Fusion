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

namespace Valvoid\Fusion\Tests\Tasks\Image\Mocks;

use Valvoid\Fusion\Group\Group;
use Valvoid\Fusion\Metadata\External\External as ExternalMeta;
use Valvoid\Fusion\Metadata\Internal\Internal as InternalMeta;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class GroupMock implements Group
{
    public array $metas = [];

    public function setImplication(array $implication): void{}

    public function setExternalMetas(array $metas): void{}
    public function getExternalMetas(): array
    {
        return [];
    }

    public function getImplication(): array{return [];}

    public function setInternalMetas(array $metas): void
    {
        $this->metas = $metas;
    }

    public function getExternalRootMetadata(): ?ExternalMeta {
        return null;
    }
    public function getInternalRootMetadata(): InternalMeta
    {
        return $this->metas[0];
    }

    public function getRootMetadata(): ExternalMeta|InternalMeta {
        return $this->metas[0];
    }
    public function hasDownloadable(): bool {
        return false;
    }
    public function getInternalMetas(): array {
        return [];
    }
    public function setImplicationBreadcrumb(array $breadcrumb): void {}
    public function getPath(string $source): array {
        return [];
    }
    public function getSourcePath(array $implication, string $source): array {
        return [];
    }
}