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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Valvoid\Fusion\Tests\Tasks\Build\Mocks;

use ReflectionClass;
use Valvoid\Fusion\Group\Proxy\Proxy;
use Valvoid\Fusion\Metadata\External\External as ExternalMeta;
use Valvoid\Fusion\Metadata\Internal\Internal as InternalMeta;
use Valvoid\Fusion\Tasks\Group;

/**
 * Mocked group proxy.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class GroupMock
{
    private ReflectionClass $reflection;

    public function __construct()
    {
        $this->reflection = new ReflectionClass(Group::class);
        $this->reflection->setStaticPropertyValue("instance", new class extends Group
        {
            public function __construct()
            {
                $this->logic = new class implements Proxy {

                    private array $implication;
                    private array $metas;

                    public function setImplication(array $implication): void
                    {
                        $this->implication = $implication;
                    }

                    public function setExternalMetas(array $metas): void
                    {
                        $this->metas = $metas;
                    }

                    public function getExternalMetas(): array
                    {
                        return $this->metas;
                    }

                    public function getImplication(): array
                    {
                        return $this->implication;
                    }

                    public function setInternalMetas(array $metas): void {}

                    public function getExternalRootMetadata(): ?ExternalMeta {
                        return null;
                    }
                    public function getInternalRootMetadata(): InternalMeta {
                        return $this->metas[-1];
                    }
                    public function getRootMetadata(): ExternalMeta|InternalMeta {
                        return $this->metas[-1];
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
                };
            }
        });
    }

    public function destroy(): void
    {
        $this->reflection->setStaticPropertyValue("instance", null);
    }
}