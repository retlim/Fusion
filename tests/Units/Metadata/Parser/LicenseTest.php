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

namespace Valvoid\Fusion\Tests\Units\Metadata\Parser;

use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Metadata;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Metadata\Parser\License\Parser;
use Valvoid\Fusion\Wrappers\Dir;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Reflex\Test\Wrapper;
use Valvoid\Reflex\Validations\Exception;

class LicenseTest extends Wrapper
{
    private Parser $parser;

    public function init(): void
    {
        $root = dirname(__DIR__, 4);
        $box = $this->createMock(Box::class);
        $bus = $this->createMock(Bus::class);
        $file = $this->createMock(File::class);
        $dir = $this->createMock(Dir::class);
        $this->parser = new Parser(
            box: $box,
            bus: $bus,
            file: $file,
            dir: $dir
        );

        $dir->fake("getDirname")
            ->expect(path: "$root/src/Metadata/Parser/License", levels: 4)
            ->return($root);

        $file->fake("get")
            ->expect(file: "$root/resources/spdx/licenses.json")
            ->return(json_encode(["licenses" => [
                ["licenseId" => "GPL-3.0-only"],
                ["licenseId" => "MIT"],
                ["licenseId" => "Apache-2.0"],
                ["licenseId" => "BSD-3-Clause"]]]))
            ->expect(file: "$root/resources/spdx/exceptions.json")
            ->return(json_encode(["exceptions" => [
                ["licenseExceptionId" => "Autoconf-exception-2.0"],
                ["licenseExceptionId" => "OCCT-exception-1.0"]
            ]]));

        parent::init();
    }

    public function prepare(): void
    {
        $this->recycleMock(Box::class);
        $this->recycleMock(Bus::class);
        $this->recycleMock(File::class);
        $this->recycleMock(Dir::class);

        parent::prepare();
    }

    public function testLicense(): void
    {
        $this->parser->parse("GPL-3.0-only");
    }

    public function testCustomLicense(): void
    {
        $this->parser->parse("LicenseRef-Test");
    }

    public function testCustomDocLicense(): void
    {
        $this->parser->parse("DocumentRef-Example:LicenseRef-Test");
    }

    public function testException(): void
    {
        $this->parser->parse("GPL-3.0-only WITH OCCT-exception-1.0");
    }

    public function testOperator(): void
    {
        $this->parser->parse("MIT AND Apache-2.0 OR BSD-3-Clause");
    }

    public function testGroup(): void
    {
        $this->parser->parse("(MIT AND (Apache-2.0 OR GPL-3.0-only))");
    }

    public function testUnknownLicense(): void
    {
        $box = $this->recycleMock(Box::class);
        $bus = $this->recycleMock(Bus::class);
        $metadata = $this->createMock(Metadata::class);

        $box->fake("get")
            ->hook(function ($class, $arguments) use ($metadata) {
                $this->validate($class)
                    ->as(Metadata::class);

                $this->validate($arguments["level"])
                    ->as(Level::ERROR);

                $this->validate($arguments["breadcrumb"])
                    ->as(["license"]);

                return $metadata;
            });

        $bus->fake("broadcast")
            ->hook(function (Metadata $event) use ($metadata) {
                $this->validate($event)
                    ->as($metadata);

                throw new Exception("#");
            });

        $this->expectException(Exception::class);
        $this->parser->parse("##");
    }

    public function testOpenGroup(): void
    {
        $this->expectException(Exception::class);
        $this->parser->parse("(##");
    }

    public function testMissingOperator(): void
    {
        $this->expectException(Exception::class);
        $this->parser->parse("MIT Apache-2.0");
    }

    public function testMissingException(): void
    {
        $this->expectException(Exception::class);
        $this->parser->parse("GPL-3.0-only WITH");
    }

    public function testClosedGroup(): void
    {
        $this->expectException(Exception::class);
        $this->parser->parse("MIT)");
    }

    public function testUnknownException(): void
    {
        $this->expectException(Exception::class);
        $this->parser->parse("MIT WITH Unknown-exception");
    }

    public function testEmptyGroup(): void
    {
        $this->expectException(Exception::class);
        $this->parser->parse("()");
    }

    public function testMissingAndOperand(): void
    {
        $this->expectException(Exception::class);
        $this->parser->parse("MIT AND");
    }

    public function testMissingOrOperand(): void
    {
        $this->expectException(Exception::class);
        $this->parser->parse("MIT OR");
    }
}