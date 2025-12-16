<?php
/*
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
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Valvoid\Fusion\Tests\Metadata\Internal\Builder;

use Throwable;
use Valvoid\Fusion\Metadata\Internal\Builder;
use Valvoid\Fusion\Tests\Metadata\Internal\Builder\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Metadata\Internal\Builder\Mocks\BusMock;
use Valvoid\Fusion\Tests\Metadata\Internal\Builder\Mocks\InternalMock;
use Valvoid\Fusion\Tests\Metadata\Internal\Builder\Mocks\InterpreterMock;
use Valvoid\Fusion\Tests\Metadata\Internal\Builder\Mocks\NormalizerMock;
use Valvoid\Fusion\Tests\Metadata\Internal\Builder\Mocks\ParserMock;
use Valvoid\Fusion\Tests\Metadata\Internal\Builder\Mocks\StructureMock;
use Valvoid\Fusion\Tests\Test;

class BuilderTest extends Test
{
    /** @var string|array  */
    protected string|array $coverage = Builder::class;
    private BoxMock $box;
    private Builder $builder;
    private BusMock $bus;
    private InterpreterMock $interpreter;
    private ParserMock $parser;
    private NormalizerMock $normalizer;
    private StructureMock $structure;
    private InternalMock $internal;

    public function __construct()
    {
        $this->box = new BoxMock;
        $this->bus = new BusMock;
        $this->interpreter = new InterpreterMock;
        $this->parser = new ParserMock;
        $this->normalizer = new NormalizerMock;
        $this->structure = new StructureMock;
        $this->internal = new InternalMock;
        $this->box->get = function (string $class, ...$args) {
            if ($class == "Valvoid\Fusion\Metadata\Normalizer\Structure") {
                $this->structure->args = $args;
                return $this->structure;
            }
            if ($class == "Valvoid\Fusion\Metadata\Internal\Internal") {
                $this->internal->args = $args;
                return $this->internal;
            }
            if ($class == "Valvoid\Fusion\Metadata\Interpreter\Interpreter")
                return $this->interpreter;

            if ($class == "Valvoid\Fusion\Metadata\Normalizer\Normalizer")
                return $this->normalizer;

            if ($class == "Valvoid\Fusion\Metadata\Parser\Parser")
                return $this->parser;
        };
        $this->structure->normalize = function (array &$meta, ?string $cache = null) {
            $meta["structure"] = [
                "cache" => "",  // legacy - rename to state
                "stateful" => "",
                "sources" => [],
                "extendables" => [],
                "mappings" => [],
                "states" => [],
                "mutables" => []
            ];
        };
        $this->interpreter->interpret = function (string $layer, mixed $entry) {};
        $this->parser->parse = function (array $meta) {};
        $this->normalizer->overlay = function (array $meta) {};
        $this->normalizer->normalize = function (array &$meta) {
            $meta["structure"] = [
                "cache" => "",  // legacy - rename to state
                "stateful" => "",
                "sources" => [],
                "extendables" => [],
                "mappings" => [],
                "states" => [],
                "mutables" => []
            ];
        };

        $this->internal->getContent = function () {
            return [
                "id" => "path",
                "version" => "2.3.4",
                "environment" => [
                    "environment",
                    "php" => [
                        "modules" => []
                    ]
                ],
                "structure" => [
                    "cache" => "",
                    "stateful" => "/state",
                    "sources" => [
                        "/dependencies" => [
                            "api/path1/1.2.3",
                            "api/path2/4.5.6"
                        ]
                    ],
                    "extensions" => [],
                    "extendables" => [],
                    "mappings" => [],
                    "namespaces" => [],
                    "states" => [],
                    "mutables" => []
                ],
                "dir" => "/dir/path",
                "source" => "/src",
                "dependencies" => [
                    "production" => [],
                    "development" => ["path1"],
                    "local" => ["path2"]
                ]
            ];
        };

        $this->builder = new Builder(
            $this->box,
            $this->bus,
            "/dir", "/src");

        // overlay metadata
        $this->testProductionLayer();
        $this->testDevelopmentLayer();
        $this->testLocalLayer();
        $this->testBotLayer();
        $this->testMetadata();

        $this->box::unsetInstance();
    }

    public function testLocalLayer(): void
    {
        $this->builder->addLocalLayer(
            ["structure" => [
                "/dependencies" => [
                    "api/path2/4.5.6"
                ]
            ]],
            "/fusion.local.php"
        );
    }

    public function testDevelopmentLayer(): void
    {
        $this->builder->addDevelopmentLayer(
            ["structure" => [
                "/dependencies" => [
                    "api/path1/1.2.3"
                ]
            ]],
            "/fusion.dev.php"
        );
    }

    public function testBotLayer(): void
    {
        // persist offset
        $this->builder->addBotLayer(
            ["version" => "2.3.4"],
            "/fusion.bot.php"
        );
    }

    public function testProductionLayer(): void
    {
        try {
            $metadata = [
                "id" => "path",
                "version" => "1.0.0",
                "environment" => ["environment"],
                "structure" => [
                    "/state" => "stateful"
                ]
            ];

            $this->builder->addProductionLayer(
                json_encode($metadata),
                "fusion.json"
            );

            return;

        } catch (Throwable $e) {
            $this->handleFailedTest();
        }
    }

    public function testMetadata(): void
    {
        if ($this->builder->getMetadata()->getContent() !== [
                "id" => "path",
                "version" => "2.3.4",
                "environment" => [
                    "environment",
                    "php" => [
                        "modules" => []
                    ]
                ],
                "structure" => [
                    "cache" => "",
                    "stateful" => "/state",
                    "sources" => [
                        "/dependencies" => [
                            "api/path1/1.2.3",
                            "api/path2/4.5.6"
                        ]
                    ],
                    "extensions" => [],
                    "extendables" => [],
                    "mappings" => [],
                    "namespaces" => [],
                    "states" => [],
                    "mutables" => []
                ],
                "dir" => "/dir/path",
                "source" => "/src",
                "dependencies" => [
                    "production" => [],
                    "development" => ["path1"],
                    "local" => ["path2"]
                ]
            ])
            $this->handleFailedTest();
    }
}