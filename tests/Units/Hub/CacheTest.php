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

namespace Valvoid\Fusion\Tests\Units\Hub;

use Valvoid\Box\Box;
use Valvoid\Fusion\Dir\Dir as Directory;
use Valvoid\Fusion\Hub\APIs\Remote\Remote;
use Valvoid\Fusion\Hub\Cache;
use Valvoid\Fusion\Util\Reference\Normalizer;
use Valvoid\Fusion\Util\Version\Interpreter;
use Valvoid\Fusion\Util\Version\Parser;
use Valvoid\Fusion\Wrappers\Dir;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Reflex\Coverage\Events\Ignore;
use Valvoid\Reflex\Test\Wrapper;

class CacheTest extends Wrapper
{
    public function __construct(Box $box)
    {
        parent::__construct($box, new Ignore(Normalizer::class,
            Interpreter::class,
            Parser::class));
    }

    public function testExpiredFilesNormalization(): void
    {
        $box = $this->createMock(Box::class);
        $directory = $this->createMock(Directory::class);
        $file = $this->createMock(File::class);
        $dir = $this->createMock(Dir::class);

        $directory->fake("getHubDir")
            ->return("#")
            ->fake("delete")
            ->expect(file: "#/#d")
            ->return(true)
            ->expect(file: "#/#f")
            ->expect(file: "#/archive.zip");

        $dir->fake("is")
            ->expect(dir: "#")
            ->return(true)
            ->expect(dir: "#/#d")
            ->expect(dir: "#/#f")
            ->return(false)
            ->expect(dir: "#/archive.zip")
            ->fake("getFilenames")
            ->expect(dir: "#")
            ->return(["#d", "#f", "archive.zip"])
            ->expect(dir: "#/#d")
            ->return([]);

        $file->fake("time")
            ->return(1614499359);

        new Cache(
            directory: $directory,
            box: $box,
            root: "#",
            dir: $dir,
            file: $file
        );
    }

    public function testReferencesLock(): void
    {
        $box = $this->recycleMock(Box::class);
        $directory = $this->createMock(Directory::class);
        $file = $this->createStub(File::class);
        $dir = $this->createMock(Dir::class);
        $source = [
            "api" => "#a",
            "path" => "#p"
        ];

        $directory->fake("getHubDir")
            ->return("#h");

        $dir->fake("is")
            ->expect(dir: "#h")
            ->return(false);

        $cache = new Cache(
            directory: $directory,
            box: $box,
            root: "#",
            dir: $dir,
            file: $file);

        // nothing
        $this->validate($cache->getReferencesState($source))
            ->as(false);

        $cache->lockReferences($source, 44);

        // active sync request
        $this->validate($cache->getReferencesState($source))
            ->as(44);

        $cache->unlockReferences($source);

        // done
        $this->validate($cache->getReferencesState($source))
            ->as(true);
    }

    public function testOffsetLock(): void
    {
        $box = $this->recycleMock(Box::class);
        $directory = $this->recycleMock(Directory::class);
        $file = $this->recycleStub(File::class);
        $dir = $this->recycleMock(Dir::class);
        $source = [
            "api" => "#a",
            "path" => "#p"
        ];

        $cache = new Cache(
            directory: $directory,
            box: $box,
            root: "#",
            dir: $dir,
            file: $file);

        // nothing
        $this->validate($cache->getOffsetState($source, "#v", "#o"))
            ->as(false);

        $cache->lockOffset($source, "#v", "#o", 44);

        // active sync request
        $this->validate($cache->getOffsetState($source, "#v", "#o"))
            ->as(44);

        // unlock
        $cache->addOffset($source, "#v", ["offset" => "#o"], "#o");

        // done
        $this->validate($cache->getOffsetState($source, "#v", "#o"))
            ->as(true);
    }

    public function testFileLock(): void
    {
        $box = $this->createMock(Box::class);
        $directory = $this->createMock(Directory::class);
        $file = $this->createMock(File::class);
        $dir = $this->createMock(Dir::class);
        $api = $this->createMock(Remote::class);
        $source = [
            "api" => "#a",
            "path" => "#p",
            "reference" => "#r",
        ];

        $directory->fake("getHubDir")
            ->return("#h")
            ->fake("createDir")
            ->expect(dir: "#h/#a#p/#r")
            ->repeat(2);

        $dir->fake("is")
            ->expect(dir: "#h")
            ->return(false);

        $file->fake("exists")
            ->expect(file: "#h/#a#p/#r#")
            ->return(false)
            ->return(true);

        $cache = new Cache(
            directory: $directory,
            box: $box,
            root: "#",
            dir: $dir,
            file: $file);

        // nothing
        $this->validate($cache->getFileState($source, "#", $api))
            ->as(false);

        $cache->lockFile($source, "#", 44);

        // active sync request
        $this->validate($cache->getFileState($source, "#", $api))
            ->as(44);

        $cache->unlockFile($source, "#");

        // done
        $this->validate($cache->getFileState($source, "#", $api))
            ->as(true);
    }

    public function testVersions(): void
    {
        $box = $this->recycleMock(Box::class);
        $directory = $this->createMock(Directory::class);
        $file = $this->createMock(File::class);
        $dir = $this->createMock(Dir::class);

        $directory->fake("getHubDir")
            ->return("#h");

        $dir->fake("is")
            ->expect(dir: "#h")
            ->return(false);

        $cache = new Cache(
            directory: $directory,
            box: $box,
            root: "#",
            dir: $dir,
            file: $file);

        $cache->addVersion("#a", "#p", "1.2.3");
        $cache->addVersion("#a", "#p", "2.3.4+aaaaaaa");
        $cache->addVersion("#a", "#p", "3.4.5-beta");

        $reference = [[
            "build" => "",
            "release" => "alpha",
            "major" => "1",
            "minor" => "0",
            "patch" => "0",
            "sign" =>  ">="
        ], "&&", [
            "build" => "",
            "release" => "",
            "major" => "3",
            "minor" => "0",
            "patch" => "0",
            "sign" =>  "<="
        ]];

        $this->validate($cache->getVersions("#a", "#p", $reference))
            ->as([
                "2.3.4+aaaaaaa",
                "1.2.3"
            ]);
    }
}