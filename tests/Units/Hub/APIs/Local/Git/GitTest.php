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

namespace Valvoid\Fusion\Tests\Units\Hub\APIs\Local\Git;

use Valvoid\Box\Box;
use Valvoid\Fusion\Hub\APIs\Local\Git\Git;
use Valvoid\Fusion\Wrappers\Program;
use Valvoid\Reflex\Test\Wrapper;

class GitTest extends Wrapper
{
    public function testRoot(): void
    {
        $box = $this->createStub(Box::class);
        $git = new Git($box,"#", []);

        $this->validate($git->getRoot())
            ->as("#");
    }

    public function testFileLocationInfo(): void
    {
        $box = $this->recycleStub(Box::class);
        $git = new Git($box,"#0", []);

        $this->validate($git->getFileLocation(
            path: "#1",
            reference: "#2",
            filename: "#3"))
            ->as("#0#1#3 | #2");
    }

    public function testReferences(): void
    {
        $box = $this->createMock(Box::class);
        $program = $this->createMock(Program::class);
        $git = new Git($box,"#0", []);

        $box->fake("get")
            ->expect(class: Program::class)
            ->return($program);

        $program->fake("execute")
            ->expect(command: "git -C #0#1 tag -l 2>&1")
            ->set(output: ["###"])
            ->return("");

        $this->validate($git->getReferences("#1")
                ->getEntries())
            ->as(["###"]);
    }

    public function testOffset(): void
    {
        $box = $this->createMock(Box::class);
        $program = $this->createMock(Program::class);
        $git = new Git($box,"#0", []);

        $box->fake("get")
            ->expect(class: Program::class)
            ->return($program);

        $program->fake("execute")
            ->expect(command: "git -C #0#1 rev-parse #o 2>&1")
            ->set(output: ["#o"])
            ->return("");

        $this->validate($git->getOffset("#1", "#o")
                ->getId())
            ->as("#o");
    }

    public function testFileContent(): void
    {
        $box = $this->createMock(Box::class);
        $program = $this->createMock(Program::class);
        $git = new Git($box,"#0", []);

        $box->fake("get")
            ->expect(class: Program::class)
            ->return($program);

        $program->fake("execute")
            ->expect(command: "git -C #0#1 show #2:3 -- 2>&1")
            ->set(output: ["###"])
            ->return("");

        $this->validate($git->getFileContent(
                path: "#1",
                reference: "#2",
                filename: "#3")->getContent())
            ->as("###");
    }

    public function testArchive(): void
    {
        $box = $this->createMock(Box::class);
        $program = $this->createMock(Program::class);
        $git = new Git($box,"#0", []);

        $box->fake("get")
            ->expect(class: Program::class)
            ->return($program);

        $program->fake("execute")
            ->expect(command: "git -C #0#1 branch --show-current 2>&1")
            ->set(output: ["#2"])
            ->return("")
            ->expect(command: "git -C #0#1 diff --name-only --diff-filter=A HEAD 2>&1")
            ->set(output: ["#f0"])
            ->expect(command: "git -C #0#1 check-ignore --no-index -- #f0 2>&1")
            ->set(output: ["#f1"])
            ->expect(command: "git -C #0#1 ls-files -o --exclude-standard 2>&1")
            ->set(output: ["#f2"])
            ->expect(command: "git -C #0#1 check-attr export-ignore -- #f0 #f2 2>&1")
            ->set(output: [])
            ->expect(command: "git -C #0#1 archive #2 --format=zip --output=#3/archive.zip " .
                " --prefix=#2/ --add-file=#f0 --prefix=#2/ " .
                "--add-file=#f2 --prefix=#2/ 2>&1");

        $this->validate($git->createArchive(
                path: "#1",
                reference: "#2",
                dir: "#3")->getFile())
            ->as("#3/archive.zip");
    }
}