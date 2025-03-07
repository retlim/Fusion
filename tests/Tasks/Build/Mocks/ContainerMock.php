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
use Valvoid\Fusion\Container\Container;
use Valvoid\Fusion\Container\Proxy\Proxy;
use Valvoid\Fusion\Metadata\External\Builder;
use Valvoid\Fusion\Metadata\External\External;
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

    public function setUpRecursiveMetadataImplication(): void
    {
        // same as
        $this->setUpExternalRootSourceImplication();
    }

    public function setUpNestedMetadataImplication(): void
    {
        // same as
        $this->setUpExternalRootSourceImplication();
    }

    public function setUpExternalRootSourceImplication(): void
    {
        $this->logic = new class implements Proxy
        {
            public $metas = [];
            public $implication = [];

            public function get(string $class, ...$args): object
            {
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
                                    "version" => [[
                                        "major" => 8,
                                        "minor" => 1,
                                        "patch" => 0,
                                        "sign" => "" // default >=
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