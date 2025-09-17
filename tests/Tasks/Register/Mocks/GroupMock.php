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

namespace Valvoid\Fusion\Tests\Tasks\Register\Mocks;

use Valvoid\Fusion\Group\Group;
use Valvoid\Fusion\Metadata\External\External as ExternalMeta;
use Valvoid\Fusion\Metadata\Internal\Internal as InternalMeta;

/**
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class GroupMock implements Group
{
    public array $implication;
    public array $internalMetas;
    public array $externalMetas;
    public bool $hasDownloadable;
    public InternalMeta $internalRoot;

    public function setImplication(array $implication): void{}

    public function setExternalMetas(array $metas): void{}
    public function getExternalMetas(): array
    {
        return $this->externalMetas;
    }

    public function getImplication(): array{
        return $this->implication;
    }

    public function setInternalMetas(array $metas): void {}

    public function getExternalRootMetadata(): ?ExternalMeta {
        return null;
    }
    public function getInternalRootMetadata(): InternalMeta
    {
        return $this->internalRoot;
    }

    public function getRootMetadata(): ExternalMeta|InternalMeta {
        return $this->externalMetas[-1];
    }
    public function hasDownloadable(): bool {
        return $this->hasDownloadable;
    }
    public function getInternalMetas(): array {
        return $this->internalMetas;
    }
    public function setImplicationBreadcrumb(array $breadcrumb): void {}
    public function getPath(string $source): array {
        return [];
    }
    public function getSourcePath(array $implication, string $source): array {
        return [];
    }
}