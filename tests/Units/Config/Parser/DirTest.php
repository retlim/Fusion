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

namespace Valvoid\Fusion\Tests\Units\Config\Parser;

use Valvoid\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Config\Parser\Dir;
use Valvoid\Fusion\Wrappers\Dir as DirWrapper;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Reflex\Test\Wrapper;

class DirTest extends Wrapper
{
   public function testSegments(): void
   {
       $box = $this->createStub(Box::class);
       $dirWrapper = $this->createStub(DirWrapper::class);
       $bus = $this->createStub(Bus::class);
       $file = $this->createStub(File::class);
       $config["dir"]["path"] = "#0/./#1\\#2/../#3";
       $dir = new Dir(
           box: $box,
           dir: $dirWrapper,
           bus: $bus,
           file: $file);

       $dir->parse($config);
       $this->validate($config["dir"]["path"])
           ->as("#0/#1/#3");
   }

   public function testRootDir(): void
   {
       $box = $this->recycleStub(Box::class);
       $dirWrapper = $this->createMock(DirWrapper::class);
       $bus = $this->recycleStub(Bus::class);
       $file = $this->createMock(File::class);
       $dir = new Dir(
           box: $box,
           dir: $dirWrapper,
           bus: $bus,
           file: $file);

       $file->fake("is")
           ->expect(file: "#0/#1/#2/fusion.json")
           ->return(false)
           ->expect(file: "#0/#1/fusion.json")
           ->expect(file: "#0/fusion.json")
           ->return(true);

       $dirWrapper->fake("getDirname")
           ->expect(path: "#0/#1/#2", levels: 1)
           ->return("#0/#1")
           ->expect(path: "#0/#1", levels: 1)
           ->return("#0");

       $this->validate($dir->getRootPath("#0/#1/#2"))
           ->as("#0");
   }
}