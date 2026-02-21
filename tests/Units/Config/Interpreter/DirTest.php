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

namespace Valvoid\Fusion\Tests\Units\Config\Interpreter;

use Exception;
use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Config;
use Valvoid\Fusion\Config\Interpreter\Dir;
use Valvoid\Fusion\Config\Parser\Dir as DirParser;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Reflex\Test\Wrapper;

class DirTest extends Wrapper
{
    public function testRootPath(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createStub(Bus::class);
        $file = $this->createMock(File::class);
        $parser = $this->createMock(DirParser::class);

        $file->fake("is")
            ->expect(file: "/#")
            ->return(false);

        $box->fake("get")
            ->expect(class: DirParser::class)
            ->return($parser);

        $parser->fake("getRootPath")
            ->expect(path: "/#")
            ->return("/#");

        $dir = new Dir($box, $bus, $file);
        $dir->interpret(["dir" => [
            "path" => "/#",
            "creatable" => true,
            "clearable" => false
        ]]);
    }

    public function testNestedPathError(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $file = $this->createMock(File::class);
        $parser = $this->createMock(DirParser::class);
        $config = $this->createStub(Config::class);
        $dir = new Dir($box, $bus, $file);

        $this->expectException(Exception::class);

        $file->fake("is")
            ->expect(file: "/#0/#1")
            ->return(false);

        $box->fake("get")
            ->expect(class: DirParser::class)
            ->return($parser)
            ->hook(function ($class, $arguments) use ($config) {
                $this->validate($class)
                    ->as(Config::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                $this->validate($arguments["breadcrumb"])
                    ->as(["dir", "path"]);

                return $config;
            });

        $parser->fake("getRootPath")
            ->expect(path: "/#0/#1")
            ->return("/#0");

        $bus->fake("broadcast")
            ->hook(function ($event) use ($config) {
                $this->validate($event)
                    ->as($config);

                // done
                throw new Exception;
            });

        $dir->interpret(["dir" => [
            "path" => "/#0/#1"
        ]]);
    }

    public function testFilePathError(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $file = $this->createMock(File::class);
        $config = $this->createStub(Config::class);
        $dir = new Dir($box, $bus, $file);

        $this->expectException(Exception::class);

        $file->fake("is")
            ->expect(file: "/#")
            ->return(true);

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($config) {
                $this->validate($class)
                    ->as(Config::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                $this->validate($arguments["breadcrumb"])
                    ->as(["dir", "path"]);

                return $config;
            });

        $bus->fake("broadcast")
            ->hook(function ($event) use ($config) {
                $this->validate($event)
                    ->as($config);

                // done
                throw new Exception;
            });

        $dir->interpret(["dir" => [
            "path" => "/#"
        ]]);
    }

    public function testInvalidWrapperType(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $file = $this->createStub(File::class);
        $config = $this->createStub(Config::class);
        $dir = new Dir($box, $bus, $file);

        $this->expectException(Exception::class);

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($config) {
                $this->validate($class)
                    ->as(Config::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                $this->validate($arguments["breadcrumb"])
                    ->as(["dir"]);

                return $config;
            });

        $bus->fake("broadcast")
            ->hook(function ($event) use ($config) {
                $this->validate($event)
                    ->as($config);

                // done
                throw new Exception;
            });

        $dir->interpret(["dir" => 222]);
    }

    public function testUnknownEntryKey(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $file = $this->createStub(File::class);
        $config = $this->createStub(Config::class);
        $dir = new Dir($box, $bus, $file);

        $this->expectException(Exception::class);

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($config) {
                $this->validate($class)
                    ->as(Config::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                $this->validate($arguments["breadcrumb"])
                    ->as(["dir", "###"]);

                return $config;
            });

        $bus->fake("broadcast")
            ->hook(function ($event) use ($config) {
                $this->validate($event)
                    ->as($config);

                // done
                throw new Exception;
            });

        $dir->interpret(["dir" => [
            "###" => "#"
        ]]);
    }

    public function testClearableError(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $file = $this->createStub(File::class);
        $config = $this->createStub(Config::class);
        $dir = new Dir($box, $bus, $file);

        $this->expectException(Exception::class);

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($config) {
                $this->validate($class)
                    ->as(Config::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                $this->validate($arguments["breadcrumb"])
                    ->as(["dir", "clearable"]);

                return $config;
            });

        $bus->fake("broadcast")
            ->hook(function ($event) use ($config) {
                $this->validate($event)
                    ->as($config);

                // done
                throw new Exception;
            });

        $dir->interpret(["dir" => [
            "clearable" => 111
        ]]);
    }

    public function testCreatableError(): void
    {
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $file = $this->createStub(File::class);
        $config = $this->createStub(Config::class);
        $dir = new Dir($box, $bus, $file);

        $this->expectException(Exception::class);

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($config) {
                $this->validate($class)
                    ->as(Config::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                $this->validate($arguments["breadcrumb"])
                    ->as(["dir", "creatable"]);

                return $config;
            });

        $bus->fake("broadcast")
            ->hook(function ($event) use ($config) {
                $this->validate($event)
                    ->as($config);

                // done
                throw new Exception;
            });

        $dir->interpret(["dir" => [
            "creatable" => 9878
        ]]);
    }
}