<?php
/**
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
 */

namespace Valvoid\Fusion\Tests\Config;

use Throwable;
use Valvoid\Fusion\Config\Interpreter\Interpreter;
use Valvoid\Fusion\Config\Logic;
use Valvoid\Fusion\Config\Normalizer\Dir;
use Valvoid\Fusion\Config\Normalizer\Normalizer;
use Valvoid\Fusion\Config\Parser\Parser;
use Valvoid\Fusion\Tests\Config\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Config\Mocks\BusMock;
use Valvoid\Fusion\Tests\Config\Mocks\DirMock;
use Valvoid\Fusion\Tests\Config\Mocks\DirNormalizerMock;
use Valvoid\Fusion\Tests\Config\Mocks\DirParserMock;
use Valvoid\Fusion\Tests\Config\Mocks\FileMock;
use Valvoid\Fusion\Tests\Config\Mocks\InterpreterMock;
use Valvoid\Fusion\Tests\Config\Mocks\NormalizerMock;
use Valvoid\Fusion\Tests\Config\Mocks\ParserMock;
use Valvoid\Fusion\Tests\Test;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class LogicTest extends Test
{
    protected string|array $coverage = Logic::class;
    private BoxMock $box;

    public function __construct()
    {
        $this->box = new BoxMock;

        $this->testDefault();
        $this->testCustomIdentifier();
        $this->testUserConfig();
        $this->testOverlayFlag();
        $this->testExtensions();

        $this->box::unsetInstance();
    }

    public function testDefault(): void
    {
        try {
            $dir = new DirMock;
            $file = new FileMock;
            $bus = new BusMock;
            $dirParser = new DirParserMock;
            $id =
            $include =
            $require =
            $interpret =
            $parse =
            $overlay =
            $normalize =
            $exists =
            $path = [];
            $dirParser->root = function ($root) use (&$path) {
                $path[] = $root;
                // same as Fusion root - own default identifier
                return "/#";
            };

            $dirNormalizer = new DirNormalizerMock;
            $dirNormalizer->normalize = function (&$config, $identifier) use (&$id) {
                $id[] = $identifier;
                // user-wide persistence
                $config["config"]["path"] = "/#c";
            };

            // default config
            $dir->filenames = function ($dir) {
                if ($dir == "/#/config")
                    return ["f0.php"];
            };

            $dir->is = fn () => false;
            $file->include = function ($file) use (&$include) {
                $include[] = $file;
                if ($file == "/#/config/f0.php")
                    return ["f0"];
            };

            $file->require = function ($file) use (&$require) {
                $require[] = $file;

                if ($file == "/#/state/extensions.php")
                    return ["/config" => [], "/extensions/config" => []];
            };

            // user-wide persistence
            $file->exists = function ($file) use (&$exists) {
                $exists[] = $file;
                return false;
            };

            $file->is = function ($file) use (&$exists) {
                return $file == "/#/state/extensions.php";
            };

            $bus->add = function () {};
            $config = new Logic(
                box: $this->box,
                root: "/#", // root
                prefixes: [],
                dir: $dir,
                file: $file,
                dirParser: $dirParser,
                config: [], // no runtime
                bus: $bus
            );

            // each config layer (default, persistence, runtime)
            // interpret, parse, normalize
            $interpreter = new InterpreterMock;
            $interpreter->interpret = function ($entry) use (&$interpret) {
                $interpret[] = $entry;
            };

            $parser = new ParserMock;
            $parser->parse = function (&$config) use (&$parse) {
                $parse[] = $config;
            };
            $normalizer = new NormalizerMock;
            $normalizer->overlay = function (&$config, $layer) use (&$overlay) {
                $config = ["common"];
                $overlay[] = [
                    "config" => $config,
                    "layer" => $layer
                ];
            };
            $normalizer->normalize = function (&$config) use (&$normalize) {
                $normalize[] = $config;
            };
            $this->box->get = function ($class) use ($dirNormalizer, $parser,
                $interpreter, $normalizer) {
                if ($class == Dir::class) return $dirNormalizer;
                if ($class == Parser::class) return $parser;
                if ($class == Interpreter::class) return $interpreter;
                if ($class == Normalizer::class) return $normalizer;
            };

            $config->load(true);

            if ($id != ["valvoid/fusion"] ||
                $path != ["/#"] ||
                $exists != ["/#/state/loadable/lazy.php", "/#c/config.json"] ||
                $require != ["/#/state/extensions.php"] ||
                $include != ["/#/config/f0.php"] ||
                $interpret != [
                    ["f0"], // default
                    ["config" => ["path" => "/#c"]]] || // runtime
                $parse != [["f0"], ["config" => ["path" => "/#c"]]] ||
                $overlay != [
                    ["config" => ["common"], "layer" => ["f0"]],
                    ["config" => ["common"], "layer" => [
                        "config" => ["path" => "/#c"]]]] ||
                $normalize != [["common"]])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testExtensions(): void
    {
        try {
            $dir = new DirMock;
            $file = new FileMock;
            $bus = new BusMock;
            $dirParser = new DirParserMock;
            $id =
            $include =
            $require =
            $interpret =
            $parse =
            $overlay =
            $normalize =
            $exists =
            $path = [];
            $dirParser->root = function ($root) use (&$path) {
                $path[] = $root;
                // same as Fusion root - own default identifier
                return "/#";
            };

            $dirNormalizer = new DirNormalizerMock;
            $dirNormalizer->normalize = function (&$config, $identifier) use (&$id) {
                $id[] = $identifier;
                // user-wide persistence
                $config["config"]["path"] = "/#c";
            };

            // default config
            $dir->filenames = function ($dir) {
                if ($dir == "/#/config")
                    return ["f0.php"];

                if ($dir == "/#/extensions/config/ex0")
                    return ["f1.php"];

                if ($dir == "/ex1")
                    return ["f2.php"];
            };

            $dir->is = function ($dir) {
                return $dir == "/#/extensions/config/ex0" ||
                    $dir == "/ex1"; // mapping absolute path
            };
            $file->include = function ($file) use (&$include) {
                $include[] = $file;
                if ($file == "/#/config/f0.php")
                    return ["f0"];

                if ($file == "/#/extensions/config/ex0/f1.php")
                    return ["f1"];

                if ($file == "/ex1/f2.php")
                    return ["f2"];
            };

            $file->require = function ($file) use (&$require) {
                $require[] = $file;

                if ($file == "/#/state/extensions.php")
                    return ["/config" => [],
                        "/extensions/config" => [
                        1 => "ex0", // legacy injection
                        4 => "/ex1", // mapping
                        // may multiple entries
                        5 => "ex0", // legacy injection
                    ]];
            };

            // user-wide persistence
            $file->exists = function ($file) use (&$exists) {
                $exists[] = $file;
                return false;
            };

            $bus->add = function () {};
            $config = new Logic(
                box: $this->box,
                root: "/#", // root
                prefixes: [],
                dir: $dir,
                file: $file,
                dirParser: $dirParser,
                config: [], // no runtime
                bus: $bus
            );

            // each config layer (default, persistence, runtime)
            // interpret, parse, normalize
            $interpreter = new InterpreterMock;
            $interpreter->interpret = function ($entry) use (&$interpret) {
                $interpret[] = $entry;
            };

            $file->is = function ($file) use (&$exists) {
                return $file == "/#/state/extensions.php";
            };
            $parser = new ParserMock;
            $parser->parse = function (&$config) use (&$parse) {
                $parse[] = $config;
            };
            $normalizer = new NormalizerMock;
            $normalizer->overlay = function (&$config, $layer) use (&$overlay) {
                $config = ["common"];
                $overlay[] = [
                    "config" => $config,
                    "layer" => $layer
                ];
            };
            $normalizer->normalize = function (&$config) use (&$normalize) {
                $normalize[] = $config;
            };
            $this->box->get = function ($class) use ($dirNormalizer, $parser,
                $interpreter, $normalizer) {
                if ($class == Dir::class) return $dirNormalizer;
                if ($class == Parser::class) return $parser;
                if ($class == Interpreter::class) return $interpreter;
                if ($class == Normalizer::class) return $normalizer;
            };

            $config->load(true);

            if ($id != ["valvoid/fusion"] ||
                $path != ["/#"] ||
                $exists != ["/#/state/loadable/lazy.php", "/#c/config.json"] ||
                $require != ["/#/state/extensions.php"] ||
                $include != [
                    "/#/config/f0.php",
                    "/#/extensions/config/ex0/f1.php",
                    "/ex1/f2.php"] ||
                $interpret != [
                    ["f0"], // default
                    ["f1"], // injection
                    ["f2"], // mapping
                    ["config" => ["path" => "/#c"]]] || // runtime
                $parse != [["f0"],["f1"],["f2"], ["config" => ["path" => "/#c"]]] ||
                $overlay != [
                    ["config" => ["common"], "layer" => ["f0"]],
                    ["config" => ["common"], "layer" => ["f1"]],
                    ["config" => ["common"], "layer" => ["f2"]],
                    ["config" => ["common"], "layer" => [
                        "config" => ["path" => "/#c"]]]] ||
                $normalize != [["common"]])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testCustomIdentifier(): void
    {
        try {
            $dir = new DirMock;
            $file = new FileMock;
            $bus = new BusMock;
            $dirParser = new DirParserMock;
            $id =
            $include =
            $require =
            $interpret =
            $parse =
            $overlay =
            $normalize =
            $exists =
            $path = [];
            $dirParser->root = function ($root) use (&$path) {
                $path[] = $root;
                // diff from Fusion root -
                // custom identifier
                return "/##";
            };

            $dirNormalizer = new DirNormalizerMock;
            $dirNormalizer->normalize = function (&$config, $identifier) use (&$id) {
                $id[] = $identifier;
                // user-wide persistence
                $config["config"]["path"] = "/#c";
            };

            // default config
            $dir->filenames = function ($dir) {
                if ($dir == "/#/config")
                    return ["f0.php"];
            };

            $dir->is = fn () => false;
            $file->include = function ($file) use (&$include) {
                $include[] = $file;
                if ($file == "/#/config/f0.php")
                    return ["f0"];
            };

            $file->require = function ($file) use (&$require) {
                $require[] = $file;

                if ($file == "/#/state/extensions.php")
                    return ["/config" => [], "/extensions/config" => []];
            };

            $file->get = function ($file) {
                if ($file == "/##/fusion.json")
                    return "{\"id\": \"i0/i0\"}";
            };

            $file->is = function ($file) use (&$exists) {
                return $file == "/#/state/extensions.php";
            };
            // user-wide persistence
            $file->exists = function ($file) use (&$exists) {
                $exists[] = $file;
                return false;
            };

            $bus->add = function () {};
            $config = new Logic(
                box: $this->box,
                root: "/#", // root
                prefixes: [],
                dir: $dir,
                file: $file,
                dirParser: $dirParser,
                config: [], // no runtime
                bus: $bus
            );

            // each config layer (default, persistence, runtime)
            // interpret, parse, normalize
            $interpreter = new InterpreterMock;
            $interpreter->interpret = function ($entry) use (&$interpret) {
                $interpret[] = $entry;
            };

            $parser = new ParserMock;
            $parser->parse = function (&$config) use (&$parse) {
                $parse[] = $config;
            };
            $normalizer = new NormalizerMock;
            $normalizer->overlay = function (&$config, $layer) use (&$overlay) {
                $config = ["common"];
                $overlay[] = [
                    "config" => $config,
                    "layer" => $layer
                ];
            };
            $normalizer->normalize = function (&$config) use (&$normalize) {
                $normalize[] = $config;
            };
            $this->box->get = function ($class) use ($dirNormalizer, $parser,
                $interpreter, $normalizer) {
                if ($class == Dir::class) return $dirNormalizer;
                if ($class == Parser::class) return $parser;
                if ($class == Interpreter::class) return $interpreter;
                if ($class == Normalizer::class) return $normalizer;
            };

            $config->load(true);

            if ($id != ["i0/i0"] ||
                $path != ["/#"] ||
                $exists != ["/#/state/loadable/lazy.php", "/#c/config.json"] ||
                $require != ["/#/state/extensions.php"] ||
                $include != ["/#/config/f0.php"] ||
                $interpret != [
                    ["f0"], // default
                    ["config" => ["path" => "/#c"]]] || // runtime
                $parse != [["f0"], ["config" => ["path" => "/#c"]]] ||
                $overlay != [
                    ["config" => ["common"], "layer" => ["f0"]],
                    ["config" => ["common"], "layer" => [
                        "config" => ["path" => "/#c"]]]] ||
                $normalize != [["common"]])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testUserConfig(): void
    {
        try {
            $dir = new DirMock;
            $file = new FileMock;
            $bus = new BusMock;
            $dirParser = new DirParserMock;
            $id =
            $include =
            $require =
            $interpret =
            $parse =
            $overlay =
            $normalize =
            $path = [];
            $dirParser->root = function ($root) use (&$path) {
                $path[] = $root;
                // same as Fusion root - own default identifier
                return "/#";
            };

            $dirNormalizer = new DirNormalizerMock;
            $dirNormalizer->normalize = function (&$config, $identifier) use (&$id) {
                $id[] = $identifier;
                // user-wide persistence
                $config["config"]["path"] = "/#c";
            };

            // default config
            $dir->filenames = function ($dir) {
                if ($dir == "/#/config")
                    return ["f0.php"];
            };

            $dir->is = fn () => false;
            $file->include = function ($file) use (&$include) {
                $include[] = $file;
                if ($file == "/#/config/f0.php")
                    return ["f0"];
            };

            $file->require = function ($file) use (&$require) {
                $require[] = $file;

                if ($file == "/#/state/extensions.php")
                    return ["/config" => [], "/extensions/config" => []];
            };

            // user-wide persistence
            $file->exists = function ($file) {
                return $file == "/#c/config.json";
            };

            $file->get = function ($file) {
                if ($file == "/#c/config.json")
                    return "[\"f1\"]";
            };

            $bus->add = function () {};
            $config = new Logic(
                box: $this->box,
                root: "/#", // root
                prefixes: [],
                dir: $dir,
                file: $file,
                dirParser: $dirParser,
                config: [], // no runtime
                bus: $bus
            );

            // each config layer (default, persistence, runtime)
            // interpret, parse, normalize
            $interpreter = new InterpreterMock;
            $interpreter->interpret = function ($entry) use (&$interpret) {
                $interpret[] = $entry;
            };

            $file->is = function ($file) use (&$exists) {
                return $file == "/#/state/extensions.php";
            };
            $parser = new ParserMock;
            $parser->parse = function (&$config) use (&$parse) {
                $parse[] = $config;
            };
            $normalizer = new NormalizerMock;
            $normalizer->overlay = function (&$config, $layer) use (&$overlay) {
                $config = ["common"];
                $overlay[] = [
                    "config" => $config,
                    "layer" => $layer
                ];
            };
            $normalizer->normalize = function (&$config) use (&$normalize) {
                $normalize[] = $config;
            };
            $this->box->get = function ($class) use ($dirNormalizer, $parser,
                $interpreter, $normalizer) {
                if ($class == Dir::class) return $dirNormalizer;
                if ($class == Parser::class) return $parser;
                if ($class == Interpreter::class) return $interpreter;
                if ($class == Normalizer::class) return $normalizer;
            };

            $config->load(true);

            if ($id != ["valvoid/fusion"] ||
                $path != ["/#"] ||
                $require != ["/#/state/extensions.php"] ||
                $include != ["/#/config/f0.php"] ||
                $interpret != [
                    ["f0"], // default
                    ["f1"], // persistence
                    ["config" => ["path" => "/#c"]]] || // runtime
                $parse != [["f0"], ["f1"], ["config" => ["path" => "/#c"]]] ||
                $overlay != [
                    ["config" => ["common"], "layer" => ["f0"]],
                    ["config" => ["common"], "layer" => ["f1"]],
                    ["config" => ["common"], "layer" => [
                        "config" => ["path" => "/#c"]]]] ||
                $normalize != [["common"]])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testOverlayFlag(): void
    {
        try {
            $dir = new DirMock;
            $file = new FileMock;
            $bus = new BusMock;
            $dirParser = new DirParserMock;
            $id =
            $include =
            $interpret =
            $parse =
            $overlay =
            $normalize =
            $path = [];
            $dirParser->root = function ($root) use (&$path) {
                $path[] = $root;
                // same as Fusion root - own default identifier
                return "/#";
            };

            $dirNormalizer = new DirNormalizerMock;
            $dirNormalizer->normalize = function (&$config, $identifier) use (&$id) {
                $id[] = $identifier;
                // user-wide persistence
                $config["config"]["path"] = "/#c";
            };

            // default config
            $dir->filenames = function ($dir) {
                if ($dir == "/#/config")
                    return ["f0.php"];
            };

            $dir->is = fn () => false;
            $file->include = function ($file) use (&$include) {
                $include[] = $file;
                if ($file == "/#/config/f0.php")
                    return ["f0"];
            };

            // user-wide persistence
            $file->exists = function ($file) {
                return $file == "/#c/config.json";
            };

            $file->get = function ($file) {
                if ($file == "/#c/config.json")
                    return "[\"f1\"]";
            };

            $bus->add = function () {};
            $config = new Logic(
                box: $this->box,
                root: "/#", // root
                prefixes: [],
                dir: $dir,
                file: $file,
                dirParser: $dirParser,
                config: ["persistence" => ["overlay" => false]],
                bus: $bus
            );

            // each config layer (default, persistence, runtime)
            // interpret, parse, normalize
            $interpreter = new InterpreterMock;
            $interpreter->interpret = function ($entry) use (&$interpret) {
                $interpret[] = $entry;
            };

            $parser = new ParserMock;
            $parser->parse = function (&$config) use (&$parse) {
                $parse[] = $config;
            };
            $normalizer = new NormalizerMock;
            $normalizer->overlay = function (&$config, $layer) use (&$overlay) {
                $config = ["common"];
                $overlay[] = [
                    "config" => $config,
                    "layer" => $layer
                ];
            };
            $normalizer->normalize = function (&$config) use (&$normalize) {
                $normalize[] = $config;
            };
            $this->box->get = function ($class) use ($dirNormalizer, $parser,
                $interpreter, $normalizer) {
                if ($class == Dir::class) return $dirNormalizer;
                if ($class == Parser::class) return $parser;
                if ($class == Interpreter::class) return $interpreter;
                if ($class == Normalizer::class) return $normalizer;
            };

            $config->load(false);

            if ($id != ["valvoid/fusion"] ||
                $path != ["/#"] ||
                $include != ["/#/config/f0.php"] ||
                $interpret != [
                    ["f0"], // default
                    [// runtime
                        "config" => ["path" => "/#c"],
                        "persistence" => ["overlay" => false]
                    ]] ||
                $parse != [["f0"], [// runtime
                    "config" => ["path" => "/#c"],
                    "persistence" => ["overlay" => false]
                ]] ||
                $overlay != [
                    ["config" => ["common"], "layer" => ["f0"]],
                    ["config" => ["common"], "layer" => [// runtime
                        "config" => ["path" => "/#c"],
                        "persistence" => ["overlay" => false]
                    ]]] ||
                $normalize != [["common"]])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }
}