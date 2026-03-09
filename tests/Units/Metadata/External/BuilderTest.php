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

namespace Valvoid\Fusion\Tests\Units\Metadata\External;

use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Metadata;
use Valvoid\Fusion\Metadata\External\Builder;
use Valvoid\Fusion\Metadata\External\External;
use Valvoid\Fusion\Metadata\External\Normalizer\Reference;
use Valvoid\Fusion\Metadata\External\Parser\Source;
use Valvoid\Fusion\Metadata\Interpreter\Interpreter;
use Valvoid\Fusion\Metadata\Normalizer\Normalizer;
use Valvoid\Fusion\Metadata\Normalizer\Structure;
use Valvoid\Fusion\Metadata\Parser\Parser;
use Valvoid\Reflex\Test\Wrapper;

class BuilderTest extends Wrapper
{
    private Builder $builder;

    public function testSource(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $source = $this->createMock(Source::class);
        $parsedSource = [
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

        $bus->fake("addReceiver")
            ->expect(id: Builder::class, events: [Metadata::class])
            ->fake("removeReceiver")
            ->expect(id: Builder::class);

        $box->fake("get")
            ->expect(class: Source::class)
            ->return($source);

        $source->fake("getId")
            ->return("path")
            ->fake("getSource")
            ->return($parsedSource);

        $this->builder = new Builder(
            box: $box,
            bus: $bus,
            dir: "/dir",
            source: "api/path/2.3.4");

        $this->validate($this->builder->getNormalizedSource())
            ->as($parsedSource);

        $this->validate($this->builder->getParsedSource())
            ->as($parsedSource);
    }

    public function testProductionLayer(): void
    {
        $box = $this->resetMock(Box::class);
        $bus = $this->recycleMock(Bus::class);
        $interpreter = $this->createMock(Interpreter::class);
        $parser = $this->createMock(Parser::class);
        $content = [
            "id" => "path",
            "version" => "2.3.4",
            "environment" => ["environment"],
            "structure" => [
                "/state" => "stateful"
            ]
        ];

        $box->fake("get")
            ->expect(class: Interpreter::class)
            ->return($interpreter)
            ->expect(class: Parser::class)
            ->return($parser);

        $interpreter->fake("interpret")
            ->expect(layer: "production", entry: $content);

        $parser->fake("parse")
            ->expect(meta: $content);

        $this->builder->addProductionLayer(
            content: json_encode($content),
            file: "fusion.json"
        );
    }

    public function testNormalizeReference(): void
    {
        $box = $this->resetMock(Box::class);
        $reference = $this->createMock(Reference::class);
        $source = [
            "api" => "api",
            "path" => "/path",
            "prefix" => "",
            "reference" => "2.3.4"
        ];

        $box->fake("get")
            ->expect(class: Reference::class)
            ->return($reference);

        $reference->fake("getNormalizedReference")
            ->expect(reference: "2.3.4")
            ->return(["reference" => "2.3.4"]);

        $this->builder->normalizeReference("2.3.4");

        $this->validate($this->builder->getNormalizedSource())
            ->as($source);

        $this->validate($this->builder->getParsedSource())
            ->as($source);
    }

    public function testId(): void
    {
        $this->validate($this->builder->getId())
            ->as("path");
    }

    public function testRawDir(): void
    {
        $this->validate($this->builder->getRawDir())
            ->as("/dir");
    }

    public function testMetadata(): void
    {
        $box = $this->resetMock(Box::class);
        $bus = $this->resetMock(Bus::class);
        $normalizer = $this->createMock(Normalizer::class);
        $structure = $this->createMock(Structure::class);
        $external = $this->createMock(External::class);

        $box->fake("get")
            ->expect(class: Normalizer::class)
            ->return($normalizer)
            ->repeat(2)
            ->expect(class: Structure::class)
            ->return($structure)
            ->hook(function ($class, $arguments) use ($external) {
                $this->validate($class)
                    ->as(External::class);

                $this->validate($arguments["layers"])
                    ->as([
                        "fusion.json" => [
                            "id" => "path",
                            "version" => "2.3.4",
                            "environment" => ["environment"],
                            "structure" => [
                                "/state" => "stateful"
                            ]
                        ],
                        "object" => [
                            "source" => "api/path/2.3.4",
                            "dir" => "/dir"
                        ]]);

                $this->validate($arguments["content"])
                    ->as(["dependencies" => ["production" => []]]);

                return $external;
            });

        $normalizer->fake("overlay")
            ->expect()
            ->repeat(1)
            ->fake("normalize")
            ->expect();

        $structure->fake("normalize")
            ->hook(function (&$meta) {
                $meta["structure"] = [
                    "sources" => []
                ];
            });

        $bus->fake("addReceiver")
            ->expect(id: Builder::class, events: [Metadata::class])
            ->repeat(1)
            ->fake("removeReceiver")
            ->expect(id: Builder::class)
            ->repeat(1);

        $this->validate($this->builder->getMetadata())
            ->as($external);
    }
}