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

namespace Valvoid\Fusion\Tests\Units\Config;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Proxy;
use Valvoid\Fusion\Config\Config;
use Valvoid\Fusion\Config\Interpreter\Interpreter;
use Valvoid\Fusion\Config\Normalizer\Normalizer;
use Valvoid\Fusion\Config\Parser\Parser;
use Valvoid\Fusion\Config\Normalizer\Dir as DirNormalizer;
use Valvoid\Fusion\Wrappers\Dir;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Reflex\Test\Wrapper;

class ConfigTest extends Wrapper
{
    public function testDefault(): void
    {
        $box = $this->createMock(Box::class);
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $bus = $this->createStub(Proxy::class);
        $interpreter = $this->createMock(Interpreter::class);
        $dirNormalizer = $this->createMock(DirNormalizer::class);
        $parser = $this->createMock(Parser::class);
        $normalizer = $this->createMock(Normalizer::class);

        $bus->fake("addReceiver")
            ->return(null);

        $box->fake("get")
            ->expect(class: DirNormalizer::class)
            ->return($dirNormalizer)

            // per config file
            ->expect(class: Interpreter::class)
            ->return($interpreter)
            ->expect(class: Parser::class)
            ->return($parser)
            ->expect(class: Normalizer::class)
            ->return($normalizer)

            // runtime
            ->expect(class: Interpreter::class)
            ->return($interpreter)
            ->expect(class: Parser::class)
            ->return($parser)
            ->expect(class: Normalizer::class)
            ->return($normalizer)

            // all configs
            ->expect(class: Normalizer::class)
            ->return($normalizer);

        $dirNormalizer->fake("normalize")
            ->expect(config: [], identifier: "valvoid/fusion")
            ->set(config: ["runtime"]);

        $dir->fake("getFilenames")
            ->expect(dir: "/#/configs/fusion") // default
            ->return(["#f0.php"]) // must have .php extension
            ->fake("is")
            ->expect(dir: "/#/configs/fusion/#f0.php")
            ->return(false);

        $file->fake("include")
            ->expect(file: "/#/configs/fusion/#f0.php")
            ->return(["#c0"]);

        $interpreter->fake("interpret")
            ->expect(entry: ["#c0"])
            ->expect(entry: ["runtime"]);

        $parser->fake("parse")
            ->expect(config: ["#c0"])
            ->set(config: ["#c1"]) // change to parsed
            ->expect(config: ["runtime"])
            ->set(config: ["#c3"]);

        $normalizer->fake("overlay")
            ->expect(config: [], layer: ["#c1"])
            ->set(config: ["#c2"])
            ->expect(config: ["#c2"], layer: ["#c3"])
            ->set(config: ["#c4"])
            ->fake("normalize")
            ->expect(config: ["#c4"])
            ->set(config: ["#c5" => "###"]);

        $config = new Config(
            box: $box,
            root: "/#", // Fusions root
            prefixes: [],
            dir: $dir,
            file: $file,
            path: "/#", // same as Fusion root - own default identifier
            config: [], // no runtime
            bus: $bus
        );

        $config->load(false);

        $this->validate($config->get())
            ->as(["#c5" => "###"]);

        $this->validate($config->get("#c5"))
            ->as("###");
    }

    public function testDefaultWithCustomIdentifier(): void
    {
        $box = $this->recycleMock(Box::class);
        $dir = $this->recycleMock(Dir::class);
        $file = $this->recycleMock(File::class);
        $bus = $this->recycleStub(Proxy::class);
        $dirNormalizer = $this->resetMock(DirNormalizer::class);

        $dirNormalizer->fake("normalize")
            ->expect(config: [], identifier: "#i0")

            // recycle existing expects
            // otherwise the path is based on #i0
            ->set(config: ["runtime"]);

        $file->fake("get")
            ->expect(file: "/##/fusion.json")
            ->return('{"id": "#i0"}');

        $config = new Config(
            box: $box,
            root: "/#", // root
            prefixes: [],
            dir: $dir,
            file: $file,
            // diff from Fusion root -
            // custom identifier
            path: "/##",
            config: [], // no runtime
            bus: $bus
        );

        $config->load(false);
    }

    public function testExtensions(): void
    {
        $box = $this->createMock(Box::class);
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $bus = $this->recycleStub(Proxy::class);
        $interpreter = $this->createMock(Interpreter::class);
        $dirNormalizer = $this->createMock(DirNormalizer::class);
        $parser = $this->createMock(Parser::class);
        $normalizer = $this->createMock(Normalizer::class);
        $runtime = ["config" => ["path" => "/#c"]]; // users home dir

        $box->fake("get")
            ->expect(class: DirNormalizer::class)
            ->return($dirNormalizer)

            // per config file
            ->expect(class: Interpreter::class)
            ->return($interpreter)
            ->expect(class: Parser::class)
            ->return($parser)
            ->expect(class: Normalizer::class)
            ->return($normalizer)
            ->expect(class: Interpreter::class)
            ->return($interpreter)
            ->expect(class: Parser::class)
            ->return($parser)
            ->expect(class: Normalizer::class)
            ->return($normalizer)

            // runtime
            ->expect(class: Interpreter::class)
            ->return($interpreter)
            ->expect(class: Parser::class)
            ->return($parser)
            ->expect(class: Normalizer::class)
            ->return($normalizer)

            // all configs
            ->expect(class: Normalizer::class)
            ->return($normalizer);

        $dirNormalizer->fake("normalize")
            ->expect(config: [], identifier: "valvoid/fusion")
            ->set(config: $runtime);

        $dir->fake("getFilenames")
            ->expect(dir: "/#/configs/fusion") // default
            ->return(["#f0.php"]) // must have .php extension
            ->expect(dir: "#e") // extension
            ->return(["#f1.php"])
            ->fake("is")
            ->expect(dir: "/#/configs/fusion/#f0.php")
            ->return(false)
            ->expect(dir: "#e/#f1.php");

        $file->fake("include")
            ->expect(file: "/#/configs/fusion/#f0.php")
            ->return(["#c0"]) // default config
            ->expect(file: "#e/#f1.php")
            ->return(["#ec0"]) // extension config
            ->fake("is")
            ->expect(file: "/#/state/extensions.php") // has extensions
            ->return(true)
            ->fake("require")
            ->expect(file: "/#/state/extensions.php")
            ->return(["/config" => ["#e"]])
            ->fake("exists")
            ->expect(file: "/#c/config.json")  // no user config
            ->return(false);

        $interpreter->fake("interpret")
            ->expect(entry: ["#c0"])
            ->expect(entry: ["#ec0"])
            ->expect(entry: $runtime);

        $parser->fake("parse")
            ->expect(config: ["#c0"])
            ->set(config: ["#c1"]) // change to parsed
            ->expect(config: ["#ec0"])
            ->set(config: ["#ec1"]) // change to parsed
            ->expect(config: $runtime)
            ->set(config: ["#c3"]);

        $normalizer->fake("overlay")
            ->expect(config: [], layer: ["#c1"])
            ->set(config: ["#c2"])
            ->expect(config: ["#c2"], layer: ["#ec1"])
            ->set(config: ["#ec2"])
            ->expect(config: ["#ec2"], layer: ["#c3"])
            ->set(config: ["#c4"])
            ->fake("normalize")
            ->expect(config: ["#c4"])
            ->set(config: ["#c5" => "###"]);

        $config = new Config(
            box: $box,
            root: "/#", // Fusions root
            prefixes: [],
            dir: $dir,
            file: $file,
            path: "/#", // same as Fusion root - own default identifier
            config: [], // no runtime
            bus: $bus
        );

        $config->load(true);

        $this->validate($config->get())
            ->as(["#c5" => "###"]);
    }

    public function testUserConfig(): void
    {
        $box = $this->createMock(Box::class);
        $dir = $this->recycleMock(Dir::class);
        $file = $this->createMock(File::class);
        $bus = $this->recycleStub(Proxy::class);
        $interpreter = $this->createMock(Interpreter::class);
        $dirNormalizer = $this->recycleMock(DirNormalizer::class);
        $parser = $this->createMock(Parser::class);
        $normalizer = $this->createMock(Normalizer::class);
        $runtime = ["config" => ["path" => "/#c"]]; // users home dir

        $box->fake("get")
            ->expect(class: DirNormalizer::class)
            ->return($dirNormalizer)

            // per config file
            // default, extension, user
            ->expect(class: Interpreter::class)
            ->return($interpreter)
            ->expect(class: Parser::class)
            ->return($parser)
            ->expect(class: Normalizer::class)
            ->return($normalizer)
            ->expect(class: Interpreter::class)
            ->return($interpreter)
            ->expect(class: Parser::class)
            ->return($parser)
            ->expect(class: Normalizer::class)
            ->return($normalizer)
            ->expect(class: Interpreter::class)
            ->return($interpreter)
            ->expect(class: Parser::class)
            ->return($parser)
            ->expect(class: Normalizer::class)
            ->return($normalizer)

            // runtime
            ->expect(class: Interpreter::class)
            ->return($interpreter)
            ->expect(class: Parser::class)
            ->return($parser)
            ->expect(class: Normalizer::class)
            ->return($normalizer)

            // all configs
            ->expect(class: Normalizer::class)
            ->return($normalizer);

        $file->fake("include")
            ->expect(file: "/#/configs/fusion/#f0.php")
            ->return(["#c0"]) // default config
            ->expect(file: "#e/#f1.php")
            ->return(["#ec0"]) // extension config
            ->fake("is")
            ->expect(file: "/#/state/extensions.php") // has extensions
            ->return(true)
            ->fake("require")
            ->expect(file: "/#/state/extensions.php")
            ->return(["/config" => ["#e"]])
            ->fake("exists")
            ->expect(file: "/#c/config.json")  // has user config
            ->return(true)
            ->fake("get")
            ->return('["#u0"]'); // json

        $interpreter->fake("interpret")
            ->expect(entry: ["#c0"])
            ->expect(entry: ["#ec0"])
            ->expect(entry: ["#u0"])
            ->expect(entry: $runtime);

        $parser->fake("parse")
            ->expect(config: ["#c0"])
            ->set(config: ["#c1"]) // change to parsed
            ->expect(config: ["#ec0"])
            ->set(config: ["#ec1"]) // change to parsed
            ->expect(config: ["#u0"])
            ->set(config: ["#u1"]) // change to parsed
            ->expect(config: $runtime)
            ->set(config: ["#c3"]);

        $normalizer->fake("overlay")
            ->expect(config: [], layer: ["#c1"])
            ->set(config: ["#c2"])
            ->expect(config: ["#c2"], layer: ["#ec1"])
            ->set(config: ["#ec2"])
            ->expect(config: ["#ec2"], layer: ["#u1"])
            ->set(config: ["#u1"])
            ->expect(config: ["#u1"], layer: ["#c3"])
            ->set(config: ["#c4"])
            ->fake("normalize")
            ->expect(config: ["#c4"])
            ->set(config: ["#c5" => "###"]);

        $config = new Config(
            box: $box,
            root: "/#", // Fusions root
            prefixes: [],
            dir: $dir,
            file: $file,
            path: "/#", // same as Fusion root - own default identifier
            config: [], // no runtime
            bus: $bus
        );

        $config->load(true);

        $this->validate($config->get())
            ->as(["#c5" => "###"]);
    }
}