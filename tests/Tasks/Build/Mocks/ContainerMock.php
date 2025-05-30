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

use Closure;
use ReflectionClass;
use Valvoid\Fusion\Container\Container;
use Valvoid\Fusion\Container\Proxy\Proxy;
use Valvoid\Fusion\Hub\Responses\Cache\Metadata;
use Valvoid\Fusion\Hub\Responses\Cache\Versions;
use Valvoid\Fusion\Log\Events\Event;
use Valvoid\Fusion\Log\Events\Interceptor;
use Valvoid\Fusion\Metadata\External\Builder;
use Valvoid\Fusion\Metadata\External\External;
use Valvoid\Fusion\Metadata\External\External as ExternalMeta;
use Valvoid\Fusion\Metadata\Internal\Internal as InternalMeta;
use Valvoid\Fusion\Tasks\Build\SAT\Solver;

/**
 * Mocked group proxy.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ContainerMock
{
    private ReflectionClass $reflection;

    public $logic;

    public function __construct()
    {
        $this->reflection = new ReflectionClass(Container::class);
    }

    public function setUpInterpreter(): void
    {
        $this->logic = new class implements Proxy
        {
            public $bus;
            public function get(string $class, ...$args): object
            {
                return $this->bus ??= new \Valvoid\Fusion\Bus\Proxy\Logic;
            }

            public function refer(string $id, string $class): void {}
            public function unset(string $class): void {}
        };

        $this->reflection->setStaticPropertyValue("instance", new class($this->logic) extends Container
        {
            public function __construct(protected Proxy $proxy) {}
        });
    }

    public function setUpRecursiveMetadataImplication(): void
    {
        $this->logic = new class implements Proxy
        {
            public $metas = [];
            public $implication = [];
            public $hub;
            public $group;

            public function get(string $class, ...$args): object
            {
                if ($class === \Valvoid\Fusion\Log\Proxy\Proxy::class)
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

                if ($class === \Valvoid\Fusion\Group\Proxy\Proxy::class)
                    return $this->group ??= new class implements \Valvoid\Fusion\Group\Proxy\Proxy
                    {
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
                        public function getInternalRootMetadata(): InternalMeta
                        {
                            return new class extends InternalMeta {
                                public function __construct() {}
                                public function getStructureSources(): array
                                {
                                    return [
                                        "" => ["metadata1"] // recursive
                                    ];
                                }
                            };
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

                if ($class === \Valvoid\Fusion\Hub\Proxy\Proxy::class)
                    return $this->hub ??= new class implements \Valvoid\Fusion\Hub\Proxy\Proxy
                    {
                        private int $counter = 0;
                        private array $versionRequests = [];
                        private array $metaRequest = [];

                        public function addVersionsRequest(array $source): int
                        {
                            $this->versionRequests[$this->counter] = $source;

                            return $this->counter++;
                        }

                        public function addMetadataRequest(array $source): int
                        {
                            $this->metaRequest[$this->counter] = $source;

                            return $this->counter++;
                        }

                        public function executeRequests(Closure $callback): void
                        {
                            while ($this->versionRequests || $this->metaRequest) {
                                foreach ($this->versionRequests as $id => $versionRequest) {
                                    unset($this->versionRequests[$id]);

                                    if ($versionRequest[0] == "metadata3")
                                        $callback(new Versions($id, ["2.30.1", "2.0.0:offset", "1.0.0"]));

                                    else
                                        $callback(new Versions($id, ["1.0.0"]));
                                }

                                foreach ($this->metaRequest as $id => $metaRequest) {
                                    unset($this->metaRequest[$id]);

                                    $callback(new Metadata($id, "", json_encode($metaRequest)));
                                }
                            }
                        }

                        public function addSnapshotRequest(array $source, string $path): int { return 0; }
                        public function addArchiveRequest(array $source): int { return 0; }
                    };

                if ($class === Solver::class) {
                    $this->implication = $args["implication"];
                    return new class extends Solver {
                        public function __construct() {}
                        public function isStructureSatisfiable(): bool
                        {
                            return true;
                        }

                        public function getPath(): array
                        {
                            // superset
                            // internal or external root
                            return [
                                "metadata1" => "1.0.0",
                                "metadata2" => "1.0.0",
                                "metadata3" => "2.0.0:offset",
                                "metadata4" => "1.0.0",
                                "metadata5" => "1.0.0",
                                "metadata6" => "1.0.0",
                                "metadata7" => "1.0.0"
                            ];
                        }
                    };
                }

                // external metadata
                return new class(...$args) extends Builder
                {
                    private string $dir;
                    private string $source;

                    private string $version;

                    public function __construct(string $dir, string $source)
                    {
                        $this->dir = $dir;
                        $this->source = $source;
                    }

                    public function getParsedSource(): array
                    {
                        // versions request
                        // pattern reference, OR, AND, sing
                        return [$this->source];
                    }

                    public function getNormalizedSource(): array
                    {
                        // metadata request
                        // version reference
                        return [
                            "source" => $this->source,
                            "version" => $this->version,
                        ];
                    }

                    public function getRawDir(): string
                    {
                        // empty root or nested
                        return $this->dir;
                    }

                    public function getMetadata(): External
                    {
                        // parsed/normalized structure
                        if ($this->source == "metadata1")
                            $structure = ["sources" => [
                                "/dir1" => [
                                    "metadata2",
                                    "metadata4"
                                ],
                                "/dir2/dir3" => [
                                    "metadata3"
                                ],
                            ]];

                        elseif ($this->source == "metadata3") {
                            $structure = ["sources" => [
                                "/dir4" => [ // nested
                                    "metadata5",
                                    "metadata2"
                                ]
                            ]];

                        } elseif ($this->source == "metadata5") {
                            $structure = ["sources" => [
                                "/dir5" => [ // nested
                                    "metadata6"
                                ],
                                "/dir6" => [ // nested
                                    "metadata7"
                                ]
                            ]];

                        } else
                            $structure = ["sources" => []];

                        return new External(layers: [], content: [
                            "id" => $this->source,
                            "name" => "", // log
                            "description" => "", // log
                            "version" => $this->version,
                            "dir" => $this->dir,  // log
                            "source" => "",  // log
                            "structure" => $structure,
                            "environment" => [
                                "php" => [
                                    "modules" => [],
                                    "version" => [[
                                        "major" => 8,
                                        "minor" => 1,
                                        "patch" => 0,
                                        "sign" => "", // default >=
                                        "release" => "",
                                        "build" => ""
                                    ]]
                                ]
                            ],
                        ]);
                    }

                    public function normalizeReference(string $reference): void
                    {
                        // extract offset before metadata requests
                        $this->version = $reference;
                    }

                    public function addProductionLayer(string $content, string $file): void
                    {
                        $content = json_decode($content, true);

                        if ($content["source"] != $this->source ||
                            $content["version"] != $this->version) {}
                    }
                };
            }

            public function unset(string $class): void {}

            public function refer(string $id, string $class): void {}
        };

        $this->reflection->setStaticPropertyValue("instance", new class($this->logic) extends Container
        {
            public function __construct(protected Proxy $proxy) {}
        });
    }

    public function setUpNestedMetadataImplication(): void
    {
        $this->logic = new class implements Proxy
        {
            public $metas = [];
            public $implication = [];
            public $hub;
            public $group;

            public function get(string $class, ...$args): object
            {
                if ($class === \Valvoid\Fusion\Log\Proxy\Proxy::class)
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

                if ($class === \Valvoid\Fusion\Group\Proxy\Proxy::class)
                    return $this->group ??= new class implements \Valvoid\Fusion\Group\Proxy\Proxy
                    {
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
                        public function getInternalRootMetadata(): InternalMeta
                        {
                            return new class extends InternalMeta {
                                public function __construct()
                                {
                                    $this->content = [
                                        "id" => "metadata1",
                                        "name" => "", // log
                                        "description" => "", // log
                                        "version" => "1.0.0",
                                        "dir" => "",  // log
                                        "source" => "",  // log
                                        "structure" => [

                                            // nested deps
                                            "sources" => [
                                                "/dir1" => [
                                                    "metadata2",
                                                    "metadata4"
                                                ],
                                                "/dir2/dir3" => [
                                                    "metadata3"
                                                ]
                                            ]
                                        ],
                                        "environment" => [
                                            "php" => [
                                                "version" => [[
                                                    "major" => 8,
                                                    "minor" => 1,
                                                    "patch" => 0,
                                                    "sign" => "" // default >=
                                                ]]
                                            ]
                                        ],
                                    ];
                                }
                            };
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

                if ($class === \Valvoid\Fusion\Hub\Proxy\Proxy::class)
                    return $this->hub ??= new class implements \Valvoid\Fusion\Hub\Proxy\Proxy
                    {
                        private int $counter = 0;
                        private array $versionRequests = [];
                        private array $metaRequest = [];

                        public function addVersionsRequest(array $source): int
                        {
                            $this->versionRequests[$this->counter] = $source;

                            return $this->counter++;
                        }

                        public function addMetadataRequest(array $source): int
                        {
                            $this->metaRequest[$this->counter] = $source;

                            return $this->counter++;
                        }

                        public function executeRequests(Closure $callback): void
                        {
                            while ($this->versionRequests || $this->metaRequest) {
                                foreach ($this->versionRequests as $id => $versionRequest) {
                                    unset($this->versionRequests[$id]);

                                    if ($versionRequest[0] == "metadata3")
                                        $callback(new Versions($id, ["2.30.1", "2.0.0:offset", "1.0.0"]));

                                    else
                                        $callback(new Versions($id, ["1.0.0"]));
                                }

                                foreach ($this->metaRequest as $id => $metaRequest) {
                                    unset($this->metaRequest[$id]);

                                    $callback(new Metadata($id, "", json_encode($metaRequest)));
                                }
                            }
                        }

                        public function addSnapshotRequest(array $source, string $path): int { return 0; }
                        public function addArchiveRequest(array $source): int { return 0; }
                    };

                if ($class === Solver::class) {
                    $this->implication = $args["implication"];
                    return new class extends Solver {
                        public function __construct() {}
                        public function isStructureSatisfiable(): bool
                        {
                            return true;
                        }

                        public function getPath(): array
                        {
                            // superset
                            // internal or external root
                            return [
                                "metadata1" => "1.0.0",
                                "metadata2" => "1.0.0",
                                "metadata3" => "2.0.0:offset",
                                "metadata4" => "1.0.0",
                                "metadata5" => "1.0.0",
                                "metadata6" => "1.0.0",
                                "metadata7" => "1.0.0"
                            ];
                        }
                    };
                }

                // external metadata
                return new class(...$args) extends Builder
                {
                    private string $dir;
                    private string $source;

                    private string $version;

                    public function __construct(string $dir, string $source)
                    {
                        $this->dir = $dir;
                        $this->source = $source;
                    }

                    public function getParsedSource(): array
                    {
                        // versions request
                        // pattern reference, OR, AND, sing
                        return [$this->source];
                    }

                    public function getNormalizedSource(): array
                    {
                        // metadata request
                        // version reference
                        return [
                            "source" => $this->source,
                            "version" => $this->version,
                        ];
                    }

                    public function getRawDir(): string
                    {
                        // empty root or nested
                        return $this->dir;
                    }

                    public function getMetadata(): External
                    {
                        // parsed/normalized structure
                        if ($this->source == "metadata1")
                            $structure = ["sources" => [
                                "/dir1" => [
                                    "metadata2",
                                    "metadata4"
                                ],
                                "/dir2/dir3" => [
                                    "metadata3"
                                ],
                            ]];

                        elseif ($this->source == "metadata3") {
                            $structure = ["sources" => [
                                "/dir4" => [ // nested
                                    "metadata5",
                                    "metadata2"
                                ]
                            ]];

                        } elseif ($this->source == "metadata5") {
                            $structure = ["sources" => [
                                "/dir5" => [ // nested
                                    "metadata6"
                                ],
                                "/dir6" => [ // nested
                                    "metadata7"
                                ]
                            ]];

                        } else
                            $structure = ["sources" => []];

                        return new External(layers: [], content: [
                            "id" => $this->source,
                            "name" => "", // log
                            "description" => "", // log
                            "version" => $this->version,
                            "dir" => $this->dir,  // log
                            "source" => "",  // log
                            "structure" => $structure,
                            "environment" => [
                                "php" => [
                                    "modules" => [],
                                    "version" => [[
                                        "major" => 8,
                                        "minor" => 1,
                                        "patch" => 0,
                                        "sign" => "", // default >=
                                        "release" => "",
                                        "build" => ""
                                    ]]
                                ]
                            ],
                        ]);
                    }

                    public function normalizeReference(string $reference): void
                    {
                        // extract offset before metadata requests
                        $this->version = $reference;
                    }

                    public function addProductionLayer(string $content, string $file): void
                    {
                        $content = json_decode($content, true);

                        if ($content["source"] != $this->source ||
                            $content["version"] != $this->version) {}
                    }
                };
            }

            public function unset(string $class): void {}

            public function refer(string $id, string $class): void {}
        };

        $this->reflection->setStaticPropertyValue("instance", new class($this->logic) extends Container
        {
            public function __construct(protected Proxy $proxy) {}
        });
    }

    public function setUpExternalRootSourceImplication(): void
    {
        $this->logic = new class implements Proxy
        {
            public $metas = [];
            public $implication = [];
            public $hub;
            public $group;

            public function get(string $class, ...$args): object
            {
                if ($class === \Valvoid\Fusion\Log\Proxy\Proxy::class)
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

                if ($class === \Valvoid\Fusion\Group\Proxy\Proxy::class)
                    return $this->group ??= new class implements \Valvoid\Fusion\Group\Proxy\Proxy
                    {
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
                        public function getInternalRootMetadata(): InternalMeta
                        {
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

                if ($class === \Valvoid\Fusion\Hub\Proxy\Proxy::class)
                    return $this->hub ??= new class implements \Valvoid\Fusion\Hub\Proxy\Proxy
                    {
                        private int $counter = 0;
                        private array $versionRequests = [];
                        private array $metaRequest = [];

                        public function addVersionsRequest(array $source): int
                        {
                            $this->versionRequests[$this->counter] = $source;

                            return $this->counter++;
                        }

                        public function addMetadataRequest(array $source): int
                        {
                            $this->metaRequest[$this->counter] = $source;

                            return $this->counter++;
                        }

                        public function executeRequests(Closure $callback): void
                        {
                            while ($this->versionRequests || $this->metaRequest) {
                                foreach ($this->versionRequests as $id => $versionRequest) {
                                    unset($this->versionRequests[$id]);

                                    if ($versionRequest[0] == "metadata3")
                                        $callback(new Versions($id, ["2.30.1", "2.0.0:offset", "1.0.0"]));

                                    else
                                        $callback(new Versions($id, ["1.0.0"]));
                                }

                                foreach ($this->metaRequest as $id => $metaRequest) {
                                    unset($this->metaRequest[$id]);

                                    $callback(new Metadata($id, "", json_encode($metaRequest)));
                                }
                            }
                        }

                        public function addSnapshotRequest(array $source, string $path): int { return 0; }
                        public function addArchiveRequest(array $source): int { return 0; }
                    };

                if ($class === Solver::class) {
                    $this->implication = $args["implication"];
                    return new class extends Solver {
                        public function __construct() {}
                        public function isStructureSatisfiable(): bool
                        {
                            return true;
                        }

                        public function getPath(): array
                        {
                            // superset
                            // internal or external root
                            return [
                                "metadata1" => "1.0.0",
                                "metadata2" => "1.0.0",
                                "metadata3" => "2.0.0:offset",
                                "metadata4" => "1.0.0",
                                "metadata5" => "1.0.0",
                                "metadata6" => "1.0.0",
                                "metadata7" => "1.0.0"
                            ];
                        }
                    };
                }

                // external metadata
                return new class(...$args) extends Builder
                {
                    private string $dir;
                    private string $source;

                    private string $version;

                    public function __construct(string $dir, string $source)
                    {
                        $this->dir = $dir;
                        $this->source = $source;
                    }

                    public function getParsedSource(): array
                    {
                        // versions request
                        // pattern reference, OR, AND, sing
                        return [$this->source];
                    }

                    public function getNormalizedSource(): array
                    {
                        // metadata request
                        // version reference
                        return [
                            "source" => $this->source,
                            "version" => $this->version,
                        ];
                    }

                    public function getRawDir(): string
                    {
                        // empty root or nested
                        return $this->dir;
                    }

                    public function getMetadata(): External
                    {
                        // parsed/normalized structure
                        if ($this->source == "metadata1")
                            $structure = ["sources" => [
                                "/dir1" => [
                                    "metadata2",
                                    "metadata4"
                                ],
                                "/dir2/dir3" => [
                                    "metadata3"
                                ],
                            ]];

                        elseif ($this->source == "metadata3") {
                            $structure = ["sources" => [
                                "/dir4" => [ // nested
                                    "metadata5",
                                    "metadata2"
                                ]
                            ]];

                        } elseif ($this->source == "metadata5") {
                            $structure = ["sources" => [
                                "/dir5" => [ // nested
                                    "metadata6"
                                ],
                                "/dir6" => [ // nested
                                    "metadata7"
                                ]
                            ]];

                        } else
                            $structure = ["sources" => []];

                        return new External(layers: [], content: [
                            "id" => $this->source,
                            "name" => "", // log
                            "description" => "", // log
                            "version" => $this->version,
                            "dir" => $this->dir,  // log
                            "source" => "",  // log
                            "structure" => $structure,
                            "environment" => [
                                "php" => [
                                    "modules" => [],
                                    "version" => [[
                                        "major" => 8,
                                        "minor" => 1,
                                        "patch" => 0,
                                        "sign" => "", // default >=
                                        "release" => "",
                                        "build" => ""
                                    ]]
                                ]
                            ],
                        ]);
                    }

                    public function normalizeReference(string $reference): void
                    {
                        // extract offset before metadata requests
                        $this->version = $reference;
                    }

                    public function addProductionLayer(string $content, string $file): void
                    {
                        $content = json_decode($content, true);

                        if ($content["source"] != $this->source ||
                            $content["version"] != $this->version) {}
                    }
                };
            }

            public function unset(string $class): void {}

            public function refer(string $id, string $class): void {}
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