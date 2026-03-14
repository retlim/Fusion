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

namespace Valvoid\Fusion\Tests\Units\Tasks\Image;

use Valvoid\Box\Box;
use Valvoid\Fusion\Config\Config;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Metadata\Internal\Builder;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tasks\Image\Image;
use Valvoid\Fusion\Wrappers\Dir;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Reflex\Test\Wrapper;

class ImageTest extends Wrapper
{
    public function testMetas(): void
    {
        $box = $this->createMock(Box::class);
        $log = $this->createMock(Log::class);
        $file = $this->createMock(File::class);
        $dir = $this->createMock(Dir::class);
        $config = $this->createMock(Config::class);
        $builder = $this->createMock(Builder::class);
        $internal = $this->createMock(Internal::class);
        $content = $this->createMock(Content::class);
        $group = $this->createMock(Group::class);
        $task = new Image(
            box: $box,
            log: $log,
            file: $file,
            dir: $dir,

            // task group id
            config: ["group" => true]
        );

        $log->fake("info")
            ->return(null)
            ->repeat(3);

        $box->fake("get")
            ->expect(class: Config::class)
            ->return($config)
            ->expect(class: Builder::class,
                arguments: ["source" => "/d0", "dir" => ""])
            ->return($builder)
            ->expect(class: Content::class, arguments: ["content" => ["i0c"]])
            ->return($content)
            ->expect(class: Builder::class,
                arguments: ["source" => "/d0/deps/i1", "dir" => "/deps"])
            ->return($builder)
            ->expect(class: Content::class, arguments: ["content" => ["i1c"]])
            ->return($content)
            ->expect(class: Builder::class,
                arguments: ["source" => "/d0/deps/i2", "dir" => "/deps"])
            ->return($builder)
            ->expect(class: Content::class, arguments: ["content" => ["i2c"]])
            ->return($content)
            ->expect(class: Group::class)
            ->return($group);

        $config->fake("get")
            ->expect(breadcrumb: ["dir", "path"])
            ->return( "/d0");

        $file->fake("exists")
            ->expect(file: "/d0/fusion.json")
            ->return(true)
            ->expect(file: "/d0/fusion.local.php")
            ->expect(file: "/d0/fusion.dev.php")
            ->expect(file: "/d0/fusion.bot.php")
            ->expect(file: "/d0/deps/fusion.json")
            ->return(false)
            ->expect(file: "/d0/deps/i1/fusion.json")
            ->return(true)
            ->expect(file: "/d0/deps/i1/fusion.bot.php")
            ->expect(file: "/d0/deps/i2/fusion.json")
            ->expect(file: "/d0/deps/i2/fusion.bot.php")
            ->return(false)
            ->fake("get")
            ->expect(file: "/d0/fusion.json")
            ->return("c0p")
            ->expect(file: "/d0/deps/i1/fusion.json")
            ->return("c1p")
            ->expect(file: "/d0/deps/i2/fusion.json")
            ->return("c2p")
            ->fake("require")
            ->expect(file: "/d0/fusion.local.php")
            ->return(["c0l"])
            ->expect(file: "/d0/fusion.dev.php")
            ->return(["c0d"])
            ->expect(file: "/d0/fusion.bot.php")
            ->return(["c0b"])
            ->expect(file: "/d0/deps/i1/fusion.bot.php")
            ->return(["c1b"]);

        $builder->fake("addProductionLayer")
            ->expect(content: "c0p", file: "/d0/fusion.json")
            ->expect(content: "c1p", file: "/d0/deps/i1/fusion.json")
            ->expect(content: "c2p", file: "/d0/deps/i2/fusion.json")
            ->fake("addLocalLayer")
            ->expect(content: ["c0l"], file: "/d0/fusion.local.php")
            ->fake("addDevelopmentLayer")
            ->expect(content: ["c0d"], file:  "/d0/fusion.dev.php")
            ->fake("addBotLayer")
            ->expect(content: ["c0b"], file: "/d0/fusion.bot.php")
            ->expect(content: ["c1b"], file: "/d0/deps/i1/fusion.bot.php")
            ->fake("getMetadata")
            ->return($internal)
            ->repeat(2);

        $internal->fake("getId")
            ->return("i0")
            ->return("i1")
            ->return("i2")
            ->fake("getContent")
            ->return(["i0c"])
            ->return(["i1c"])
            ->return(["i2c"])
            ->fake("getStructureSources")
            ->return(["/deps" => [
                "/i1" => "#",
                "/i2" => "#"
            ]]);

        $dir->fake("is")
            ->expect(dir: "/d0/deps")
            ->return(true)
            ->expect(dir: "/d0/deps/i1")
            ->expect(dir: "/d0/deps/i2")
            ->fake("getFilenames")
            ->expect(dir: "/d0/deps")
            ->return(["i1", "i2"]);

        $group->fake("setInternalMetas")
            ->expect(metas: [
                "i0" => $internal,
                "i1" => $internal,
                "i2" => $internal
            ]);

        $task->execute();
    }
}