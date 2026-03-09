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

namespace Valvoid\Fusion\Tests\Units\Metadata\Internal;

use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Metadata;
use Valvoid\Fusion\Metadata\Internal\Builder;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Metadata\Interpreter\Interpreter;
use Valvoid\Fusion\Metadata\Normalizer\Normalizer;
use Valvoid\Fusion\Metadata\Normalizer\Structure;
use Valvoid\Fusion\Metadata\Parser\Parser;
use Valvoid\Reflex\Test\Wrapper;

class BuilderTest extends Wrapper
{
    private Builder $builder;

    public function init(): void
    {
        parent::init();

        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $this->builder = new Builder(
            box: $box,
            bus: $bus,
            dir: "/dir",
            source: "/src"
        );
    }

    public function testLocalLayer(): void
    {
        $box = $this->resetMock(Box::class);
        $bus = $this->resetMock(Bus::class);
        $interpreter = $this->createMock(Interpreter::class);
        $parser = $this->createMock(Parser::class);
        $content = ["structure" => [
            "/dependencies" => [
                "api/path2/4.5.6"
            ]
        ]];

        $box->fake("get")
            ->expect(class: Interpreter::class)
            ->return($interpreter)
            ->expect(class: Parser::class)
            ->return($parser);

        $bus->fake("addReceiver")
            ->expect(id: Builder::class, events: [Metadata::class])
            ->fake("removeReceiver")
            ->expect(id: Builder::class);

        $interpreter->fake("interpret")
            ->expect(layer: "local", entry: $content);

        $parser->fake("parse")
            ->expect(meta: $content);

        $this->builder->addLocalLayer(
            content: $content,
            file: "/fusion.local.php"
        );
    }

    public function testDevelopmentLayer(): void
    {
        $box = $this->resetMock(Box::class);
        $bus = $this->resetMock(Bus::class);
        $interpreter = $this->resetMock(Interpreter::class);
        $parser = $this->resetMock(Parser::class);
        $content = ["structure" => [
            "/dependencies" => [
                "api/path1/1.2.3"
            ]
        ]];

        $box->fake("get")
            ->expect(class: Interpreter::class)
            ->return($interpreter)
            ->expect(class: Parser::class)
            ->return($parser);

        $bus->fake("addReceiver")
            ->expect(id: Builder::class, events: [Metadata::class])
            ->fake("removeReceiver")
            ->expect(id: Builder::class);

        $interpreter->fake("interpret")
            ->expect(layer: "development", entry: $content);

        $parser->fake("parse")
            ->expect(meta: $content);

        $this->builder->addDevelopmentLayer(
            content: $content,
            file: "/fusion.dev.php"
        );
    }

    public function testBotLayer(): void
    {
        $box = $this->resetMock(Box::class);
        $bus = $this->resetMock(Bus::class);
        $interpreter = $this->resetMock(Interpreter::class);
        $parser = $this->resetMock(Parser::class);
        $content = ["version" => "2.3.4"];

        $box->fake("get")
            ->expect(class: Interpreter::class)
            ->return($interpreter)
            ->expect(class: Parser::class)
            ->return($parser);

        $bus->fake("addReceiver")
            ->expect(id: Builder::class, events: [Metadata::class])
            ->fake("removeReceiver")
            ->expect(id: Builder::class);

        $interpreter->fake("interpret")
            ->expect(layer: "bot", entry: $content);

        $parser->fake("parse")
            ->expect(meta: $content);

        // persist offset
        $this->builder->addBotLayer(
            content: $content,
            file: "/fusion.bot.php"
        );
    }

    public function testProductionLayer(): void
    {
        $box = $this->resetMock(Box::class);
        $bus = $this->resetMock(Bus::class);
        $interpreter = $this->resetMock(Interpreter::class);
        $parser = $this->resetMock(Parser::class);
        $content = [
            "id" => "path",
            "version" => "1.0.0",
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

        $bus->fake("addReceiver")
            ->expect(id: Builder::class, events: [Metadata::class])
            ->fake("removeReceiver")
            ->expect(id: Builder::class);

        $interpreter->fake("interpret")
            ->expect(layer: "production", entry: $content);

        $parser->fake("parse")
            ->expect(meta: $content);

        $this->builder->addProductionLayer(
            content: json_encode($content),
            file: "/fusion.json"
        );
    }

    public function testMetadata(): void
    {
        $box = $this->resetMock(Box::class);
        $bus = $this->resetMock(Bus::class);
        $normalizer = $this->createMock(Normalizer::class);
        $structure = $this->createMock(Structure::class);
        $internal = $this->createMock(Internal::class);

        $box->fake("get")
            ->expect(class: Normalizer::class)
            ->return($normalizer)
            // 1 common + 5 layers - object, bot, pro, dev, local
            ->repeat(5)
            ->expect(class: Structure::class)
            ->return($structure)
            ->repeat(2)
            ->hook(function ($class, $arguments) use ($internal) {
                $this->validate($class)
                    ->as(Internal::class);

                $layers = $arguments["layers"];

                $this->validate($layers["/fusion.bot.php"])
                    ->as(["version" => "2.3.4"]);

                $this->validate($layers["object"])
                    ->as(["dir" => "/dir", "source" => "/src"]);

                $this->validate($layers["/fusion.dev.php"])
                    ->as(["structure" => [
                        "/dependencies" => [
                            "api/path1/1.2.3"
                        ]]]);

                $this->validate($layers["/fusion.local.php"])
                    ->as(["structure" => [
                        "/dependencies" => [
                            "api/path2/4.5.6"
                        ]]]);

                $this->validate($layers["/fusion.json"])
                    ->as(["id" => "path",
                        "version" => "1.0.0",
                        "environment" => ["environment"],
                        "structure" => [
                            "/state" => "stateful"
                        ]]);

                return $internal;
            });

        $normalizer->fake("overlay")
            ->expect()
            ->repeat(4)
            ->fake("normalize")
            ->expect();

        $structure->fake("normalize")
            ->hook(function (&$meta) {
                $meta["structure"] = [
                    "sources" => []
                ];

            })->repeat(2);

        $bus->fake("addReceiver")
            ->expect(id: Builder::class, events: [Metadata::class])
            ->repeat(4)
            ->fake("removeReceiver")
            ->expect(id: Builder::class)
            ->repeat(4);

        $this->validate($this->builder->getMetadata())
            ->as($internal);
    }
}