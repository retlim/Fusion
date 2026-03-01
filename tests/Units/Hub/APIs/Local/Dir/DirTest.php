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

namespace Valvoid\Fusion\Tests\Units\Hub\APIs\Local\Dir;

use PharData;
use Valvoid\Box\Box;
use Valvoid\Fusion\Hub\APIs\Local\Dir\Dir;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Reflex\Test\Wrapper;

class DirTest extends Wrapper
{
    public function testRoot(): void
    {
        $box = $this->createStub(Box::class);
        $dir = new Dir($box,"#", []);

        $this->validate($dir->getRoot())
            ->as("#");
    }

    public function testFileLocationInfo(): void
    {
        $box = $this->recycleStub(Box::class);
        $dir = new Dir($box,"#0", []);

        $this->validate($dir->getFileLocation(
                path: "#1",
                reference: "#2",
                filename: "#3"))
            ->as("#0#1#3 | #2");
    }

    public function testReferences(): void
    {
        $box = $this->createMock(Box::class);
        $file = $this->createMock(File::class);
        $dir = new Dir($box,"#0", []);

        $box->fake("get")
            ->expect(class: File::class)
            ->return($file);

        $file->fake("exists")
            ->expect(file: "#0#1/fusion.json")
            ->return(true)
            ->fake("get")
            ->expect(file: "#0#1/fusion.json")
            ->return('{"version":"#2"}');

        $this->validate($dir->getReferences("#1")
            ->getEntries())
            ->as(["#2"]);
    }

    public function testFileContent(): void
    {
        $box = $this->createMock(Box::class);
        $file = $this->createMock(File::class);
        $dir = new Dir($box,"#", []);

        $box->fake("get")
            ->expect(class: File::class)
            ->return($file)
            ->repeat(1);

        $file->fake("exists")
            ->expect(file: "##1/fusion.json")
            ->return(true)
            ->expect(file: "##1#3")
            ->fake("get")
            ->expect(file: "##1/fusion.json")
            ->return('{"version":"#2"}')
            ->expect(file: "##1#3")
            ->return("###");

        $this->validate($dir->getFileContent(
                path: "#1",
                reference: "#2",
                filename: "#3")->getContent())
            ->as("###");
    }

    public function testArchive(): void
    {
        $box = $this->createMock(Box::class);
        $file = $this->createMock(File::class);
        $phar = $this->createStub(PharData::class);
        $dir = new Dir($box,"#0", []);

        $box->fake("get")
            ->expect(class: File::class)
            ->return($file)
            ->expect(class: PharData::class,
                arguments: ["filename" => "#3/archive.zip"])
            ->return($phar);

        $phar->fake("buildFromDirectory")
            ->return(true)
            ->fake("__destruct")
            ->return(true);

        $file->fake("exists")
            ->expect(file: "#0#1/fusion.json")
            ->return(true)
            ->fake("get")
            ->expect(file: "#0#1/fusion.json")
            ->return('{"version":"#2"}');

        $this->validate($dir->createArchive(
                path: "#1",
                reference: "#2",
                dir: "#3")->getFile())
            ->as("#3/archive.zip");
    }
}