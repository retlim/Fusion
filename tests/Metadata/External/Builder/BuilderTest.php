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

namespace Valvoid\Fusion\Tests\Metadata\External\Builder;

use Throwable;
use Valvoid\Fusion\Metadata\External\Builder;
use Valvoid\Fusion\Tests\Metadata\External\Builder\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Metadata\External\Builder\Mocks\BusMock;
use Valvoid\Fusion\Tests\Metadata\External\Builder\Mocks\ExternalMock;
use Valvoid\Fusion\Tests\Metadata\External\Builder\Mocks\NormalizerMock;
use Valvoid\Fusion\Tests\Metadata\External\Builder\Mocks\InterpreterMock;
use Valvoid\Fusion\Tests\Metadata\External\Builder\Mocks\ParserMock;
use Valvoid\Fusion\Tests\Metadata\External\Builder\Mocks\ReferenceMock;
use Valvoid\Fusion\Tests\Metadata\External\Builder\Mocks\SourceMock;
use Valvoid\Fusion\Tests\Metadata\External\Builder\Mocks\StructureMock;
use Valvoid\Fusion\Tests\Test;

class BuilderTest extends Test
{
    protected string|array $coverage = Builder::class;

    private BoxMock $box;
    private Builder $builder;
    private BusMock $bus;
    private SourceMock $source;
    private ReferenceMock $reference;
    private NormalizerMock $normalizer;
    private InterpreterMock $interpreter;
    private ParserMock $parser;
    private StructureMock $structure;
    private ExternalMock $external;

    public function __construct()
    {
        $this->box = new BoxMock;
        $this->bus = new BusMock;
        $this->source = new SourceMock;
        $this->reference = new ReferenceMock;
        $this->interpreter = new InterpreterMock;
        $this->normalizer = new NormalizerMock;
        $this->parser = new ParserMock;
        $this->structure = new StructureMock;
        $this->external = new ExternalMock;
        $this->box->get = function (string $class, ...$args) {
            if ($class == "Valvoid\Fusion\Metadata\External\Parser\Source") {
                $this->source->args = $args;
                return $this->source;
            }

            if ($class == "Valvoid\Fusion\Metadata\Normalizer\Structure") {
                $this->structure->args = $args;
                return $this->structure;
            }

            if ($class == "Valvoid\Fusion\Metadata\External\External") {
                $this->external->args = $args;
                return $this->external;
            }

            if ($class == "Valvoid\Fusion\Metadata\External\Normalizer\Reference")
                return $this->reference;

            if ($class == "Valvoid\Fusion\Metadata\Interpreter\Interpreter")
                return $this->interpreter;

            if ($class == "Valvoid\Fusion\Metadata\Normalizer\Normalizer")
                return $this->normalizer;

            if ($class == "Valvoid\Fusion\Metadata\Parser\Parser")
                return $this->parser;
        };

        $this->interpreter->interpret = function (string $layer, mixed $entry) {};
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

        $this->parser->parse = function (array $meta) {};
        $this->external->getContent = function () {
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
                    "sources" => [],
                    "extendables" => [],
                    "mappings" => [],
                    "states" => [],
                    "mutables" => []
                ],
                "source" => [
                    "api" => "api",
                    "path" => "/path",
                    "prefix" => "",
                    "reference" => "2.3.4"
                ],
                "dir" => "/dir/path",
                "dependencies" => [
                    "production" => []
                ]
            ];
        };

        $this->normalizer->overlay = function (array $meta) {};
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

        $this->source->getId = function () {
            if ($this->source->args == ["source" => "api/path/2.3.4"])
                return "path";
        };

        $this->source->getSource = function () {
            if ($this->source->args == ["source" => "api/path/2.3.4"])
                return [
                    "api" => "api",
                    "path" => "/path",
                    "prefix" => "",
                    "reference" => [[
                        "build" => "",
                        "release" => "",
                        "major" => "2",
                        "minor" => "3",
                        "patch" => "4",
                        "sign" => ""
                    ]]
                ];
        };

        $this->reference->getNormalizedReference = function (string $reference)
        {
            if ($reference == "2.3.4")
                return ["reference" => $reference];

            return ["reference" => "###"];
        };

        $this->builder = new Builder(
            $this->box,
            $this->bus,
            "/dir", "api/path/2.3.4");

        $this->testId();
        $this->testRawDir();

        // pattern reference
        $this->testSource();

        // absolute/pointer top reference
        $this->testNormalizeReference();
        $this->testProductionLayer();
        $this->testMetadata();
    }

    public function testProductionLayer(): void
    {
        try {
            $metadata = [
                "id" => "path",
                "version" => "2.3.4",
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
                    "sources" => [],
                    "extendables" => [],
                    "mappings" => [],
                    "states" => [],
                    "mutables" => []
                ],
                "source" => [
                    "api" => "api",
                    "path" => "/path",
                    "prefix" => "",
                    "reference" => "2.3.4"
                ],
                "dir" => "/dir/path",
                "dependencies" => [
                    "production" => []
                ]
            ])
            $this->handleFailedTest();
    }

    public function testNormalizeReference(): void
    {
        $source = [
            "api" => "api",
            "path" => "/path",
            "prefix" => "",
            "reference" => "2.3.4"
        ];

        $this->builder->normalizeReference("2.3.4");

        if ($this->builder->getNormalizedSource() !== $source ||
            $this->builder->getParsedSource() !== $source)
            $this->handleFailedTest();
    }

    public function testSource(): void
    {
        $source = [
            "api" => "api",
            "path" => "/path",
            "prefix" => "",
            "reference" => [[
                "build" => "",
                "release" => "",
                "major" => "2",
                "minor" => "3",
                "patch" => "4",
                "sign" => ""
            ]]
        ];

        if ($this->builder->getNormalizedSource() !== $source ||
            $this->builder->getParsedSource() !== $source)
            $this->handleFailedTest();
    }

    public function testId(): void
    {
        if ($this->builder->getId() !== "path")
            $this->handleFailedTest();
    }

    public function testRawDir(): void
    {
        if ($this->builder->getRawDir() !== "/dir")
            $this->handleFailedTest();
    }
}