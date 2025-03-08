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

namespace Valvoid\Fusion\Tests\Tasks\Replicate\Mocks;

use Closure;
use ReflectionClass;
use Valvoid\Fusion\Container\Container;
use Valvoid\Fusion\Container\Proxy\Proxy;
use Valvoid\Fusion\Dir\Proxy\Logic;
use Valvoid\Fusion\Hub\Responses\Cache\Metadata;
use Valvoid\Fusion\Log\Events\Event;
use Valvoid\Fusion\Log\Events\Interceptor;

/**
 * Mocked container.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ContainerMock
{
    private ReflectionClass $reflection;

    public function __construct()
    {
        $this->reflection = new ReflectionClass(Container::class);
        $this->reflection->setStaticPropertyValue("instance", new class extends Container
        {
            public function __construct()
            {
                $this->proxy = new class implements Proxy {

                    public $group;
                    public $bus;

                    public function get(string $class, ...$args): object
                    {
                        if ($class === \Valvoid\Fusion\Group\Proxy\Proxy::class)
                            return $this->group ??= new \Valvoid\Fusion\Group\Proxy\Logic();

                        if ($class === \Valvoid\Fusion\Bus\Proxy\Proxy::class)
                            return $this->bus ??= new \Valvoid\Fusion\Bus\Proxy\Logic();

                        if ($class === \Valvoid\Fusion\Dir\Proxy\Proxy::class)
                            return new class extends Logic
                            {
                                public function __construct()
                                {
                                    $this->root = __DIR__ . "/package";
                                    $this->cache = __DIR__ . "/package/cache";
                                }
                            };

                        if ($class === \Valvoid\Fusion\Hub\Proxy\Proxy::class)
                            return new class implements \Valvoid\Fusion\Hub\Proxy\Proxy
                            {
                                public function addVersionsRequest(array $source): int
                                {
                                    return 0;
                                }

                                public function addMetadataRequest(array $source): int
                                {
                                    return $this->addFileRequest($source,

                                        // allow only json and
                                        // only important file request
                                        // block dynamic files
                                        "","/fusion.json");
                                }

                                public function addSnapshotRequest(array $source, string $path): int
                                {
                                    return $this->addFileRequest($source,

                                        // allow only json and
                                        // only important file request
                                        // block dynamic files
                                        $path, "/snapshot.json");
                                }

                                public function addArchiveRequest(array $source): int
                                {
                                    return 0;
                                }

                                protected function addFileRequest(array $source, string $path, string $file): int
                                {
                                    // fake request id
                                    return match ($source['path']) {
                                        "/test/local" => 1,
                                        "/test/development" => 2,

                                        // /test/production
                                        default => 3
                                    };
                                }

                                public function executeRequests(Closure $callback): void
                                {
                                    $callback(new Metadata(1, __DIR__, MetadataMock::get("local")));
                                    $callback(new Metadata(2, __DIR__, MetadataMock::get("development")));
                                    $callback(new Metadata(3, __DIR__, MetadataMock::get("production")));
                                }
                            };

                        return new class implements \Valvoid\Fusion\Log\Proxy\Proxy
                        {
                            public function addInterceptor(Interceptor $interceptor): void {}
                            public function removeInterceptor(): void {}
                            public function error(string|Event $event): void {}
                            public function warning(string|Event $event): void {}
                            public function notice(string|Event $event): void {}
                            public function info(string|Event $event): void {}
                            public function verbose(string|Event $event): void {}
                            public function debug(string|Event $event): void {}
                        };
                    }

                    public function refer(string $id, string $class): void {}
                    public function unset(string $class): void {}
                };
            }
        });
    }

    public function destroy(): void
    {
        $this->reflection->setStaticPropertyValue("instance", null);
    }
}