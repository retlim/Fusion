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

namespace Valvoid\Fusion\Tests\Metadata\Internal\Mocks;

use ReflectionClass;
use Valvoid\Fusion\Container\Container;
use Valvoid\Fusion\Container\Proxy\Proxy;
use Valvoid\Fusion\Dir\Proxy\Proxy as DirProxy;
use Valvoid\Fusion\Log\Events\Event;
use Valvoid\Fusion\Log\Events\Interceptor;
use Valvoid\Fusion\Log\Proxy\Proxy as LogProxy;
use Valvoid\Fusion\Group\Proxy\Proxy as GroupProxy;
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

    public $proxy;

    public function __construct()
    {
        $this->proxy = new class implements Proxy {

            public $log;
            public $dir;
            public $group;

            public function __construct()
            {
                $this->dir = new class implements DirProxy
                {
                    public function getStateDir(): string
                    {
                        return __DIR__;
                    }

                    public function getTaskDir(): string {return "";}
                    public function getCacheDir(): string {return "";}
                    public function getOtherDir(): string {return "";}
                    public function getPackagesDir(): string
                    {
                        return "";
                    }
                    public function getRootDir(): string {return "";}
                    public function createDir(string $dir, int $permissions): void {}
                    public function rename(string $from, string $to): void {}
                    public function copy(string $from, string $to): void {}
                    public function delete(string $file): void {}
                    public function clear(string $dir, string $path): void {}
                };
                $this->log = new class implements LogProxy
                {
                    public $event = "";

                    public function addInterceptor(Interceptor $interceptor): void {}
                    public function removeInterceptor(): void {}
                    public function error(string|Event $event): void {}
                    public function warning(string|Event $event): void {}
                    public function notice(string|Event $event): void {}
                    public function info(string|Event $event): void {}
                    public function verbose(string|Event $event): void {}
                    public function debug(string|Event $event): void
                    {
                        $this->event = $event;
                    }
                };
                $this->group = new class implements GroupProxy
                {
                    public function setInternalMetas(array $metas): void {}
                    public function setImplication(array $implication): void {}
                    public function setExternalMetas(array $metas): void {}
                    public function getExternalRootMetadata(): ?ExternalMeta {return null;}
                    public function getInternalRootMetadata(): InternalMeta {return new InternalMeta([],[]);}
                    public function getRootMetadata(): ExternalMeta|InternalMeta {return new InternalMeta([],[]);}
                    public function hasDownloadable(): bool {return false;}
                    public function getExternalMetas(): array
                    {
                        return [
                            "identifier" => new class extends ExternalMeta
                            {
                                public function __construct() {}

                                public function getId(): string
                                {
                                    return "id";
                                }

                                public function getVersion(): string
                                {
                                    return "version";
                                }
                            }
                        ];
                    }
                    public function getInternalMetas(): array {return [];}
                    public function setImplicationBreadcrumb(array $breadcrumb): void {}
                    public function getImplication(): array {return [];}
                    public function getPath(string $source): array {return [];}
                    public function getSourcePath(array $implication, string $source): array {return [];}
                };
            }

            public function get(string $class, ...$args): object
            {
                return match($class) {
                    "Valvoid\Fusion\Dir\Proxy\Proxy" => $this->dir,
                    "Valvoid\Fusion\Log\Proxy\Proxy" => $this->log,
                    "Valvoid\Fusion\Group\Proxy\Proxy" => $this->group
                };
            }

            public function refer(string $id, string $class): void {}
            public function unset(string $class): void {}
        };
        $this->reflection = new ReflectionClass(Container::class);
        $this->reflection->setStaticPropertyValue("instance", new class($this->proxy) extends Container
        {
            public function __construct(protected Proxy $proxy){}
        });
    }

    public function destroy(): void
    {
        $this->reflection->setStaticPropertyValue("instance", null);
    }
}