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

namespace Valvoid\Fusion\Tests\Group\Mocks;

use ReflectionClass;
use Valvoid\Fusion\Container\Container;
use Valvoid\Fusion\Container\Proxy\Proxy;
use Valvoid\Fusion\Metadata\External\External as ExternalMeta;
use Valvoid\Fusion\Metadata\Internal\Internal as InternalMeta;

/**
 * Mocked container.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ContainerMock
{
    private ReflectionClass $reflection;
    public Proxy $logic;
    public function __construct()
    {
        $this->reflection = new ReflectionClass(Container::class);
        $this->logic = new class implements Proxy
        {
            public \Valvoid\Fusion\Group\Proxy\Proxy $group;
            public function get(string $class, ...$args): object
            {
                return $this->group ??= new class implements \Valvoid\Fusion\Group\Proxy\Proxy
                {
                    public $calls = [];

                    public function setInternalMetas(array $metas): void
                    {
                        $this->calls[] = __FUNCTION__;
                    }

                    public function setImplication(array $implication): void
                    {
                        $this->calls[] = __FUNCTION__;
                    }

                    public function setExternalMetas(array $metas): void
                    {
                        $this->calls[] = __FUNCTION__;
                    }

                    public function getExternalRootMetadata(): ?ExternalMeta
                    {
                        $this->calls[] = __FUNCTION__;

                        return null;
                    }

                    public function getInternalRootMetadata(): InternalMeta
                    {
                        $this->calls[] = __FUNCTION__;

                        return new InternalMeta([],[]);
                    }

                    public function getRootMetadata(): ExternalMeta|InternalMeta
                    {
                        $this->calls[] = __FUNCTION__;
                        return new InternalMeta([],[]);
                    }

                    public function hasDownloadable(): bool
                    {
                        $this->calls[] = __FUNCTION__;
                        return true;
                    }

                    public function getExternalMetas(): array
                    {
                        $this->calls[] = __FUNCTION__;
                        return [];
                    }

                    public function getInternalMetas(): array
                    {
                        $this->calls[] = __FUNCTION__;
                        return [];
                    }

                    public function setImplicationBreadcrumb(array $breadcrumb): void
                    {
                        $this->calls[] = __FUNCTION__;
                    }

                    public function getImplication(): array
                    {
                        $this->calls[] = __FUNCTION__;
                        return [];
                    }

                    public function getPath(string $source): array
                    {
                        $this->calls[] = __FUNCTION__;
                        return [];
                    }

                    public function getSourcePath(array $implication, string $source): array
                    {
                        $this->calls[] = __FUNCTION__;
                        return [];
                    }
                };
            }

            public function refer(string $id, string $class): void {}
            public function unset(string $class): void {}
        };

        $this->reflection->setStaticPropertyValue("instance", new class($this->logic) extends Container
        {
            public function __construct(protected Proxy $proxy) {}
        });
    }

    public function destroy(): void
    {
        $this->reflection->setStaticPropertyValue("instance", null);
    }
}