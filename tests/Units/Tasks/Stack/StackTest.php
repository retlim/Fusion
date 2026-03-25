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

namespace Valvoid\Fusion\Tests\Units\Tasks\Stack;

use Valvoid\Box\Box;
use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Metadata\External\External;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tasks\Stack\Stack;
use Valvoid\Reflex\Test\Wrapper;

class StackTest extends Wrapper
{
    public function testStack(): void
    {
        $box = $this->createMock(Box::class);
        $log = $this->createMock(Log::class);
        $directory = $this->createMock(Dir::class);
        $group = $this->createMock(Group::class);
        $internal = $this->createMock(Internal::class);
        $external = $this->createMock(External::class);
        $content = $this->createStub(Content::class);
        $task = new Stack(
            box: $box,
            group: $group,
            log: $log,
            directory: $directory,
            config: []
        );

        $log->fake("info")
            ->return(null)
            ->repeat(4);

        $group->fake("hasDownloadable")
            ->return(true)
            ->fake("getExternalRootMetadata")
            ->return(null)
            ->fake("getInternalRootMetadata")
            ->return($internal)
            ->fake("getExternalMetas")
            ->return([
                "i2" => $external,
                "i3" => $external,
                "i4" => $external
            ])
            ->fake("getInternalMetas")
            ->return([
                "i0" => $internal,
                "i1" => $internal,
                "i2" => $internal,
                "i3" => $internal
            ])
            ->fake("getImplication")
            ->return([
                "i3" => ["implication" => []],
                "i4" => ["implication" => [
                    "i2" => ["implication" => []]
                ]]
            ]);

        $internal->fake("getContent")
            ->return(["###"])
            ->repeat(2)
            ->fake("getId")
            ->return("i0")
            ->fake("getDir")
            ->return("")
            ->return("/deps/d2")
            ->return("/deps/d3")
            ->fake("getCategory")
            ->return(InternalCategory::RECYCLABLE) // i0
            ->return(InternalCategory::OBSOLETE) // i1
            ->return(InternalCategory::MOVABLE) // i2
            ->return(InternalCategory::RECYCLABLE) // i3
            ->fake("onCopy")
            ->return(true);

        $external->fake("getCategory")
            ->return(ExternalCategory::REDUNDANT)
            ->return(ExternalCategory::REDUNDANT)
            ->return(ExternalCategory::DOWNLOADABLE)
            ->return(ExternalCategory::REDUNDANT) // hooks loop
            ->return(ExternalCategory::REDUNDANT)
            ->return(ExternalCategory::DOWNLOADABLE)
            ->fake("getDir")
            ->return("/deps/d2") // i2 internal movable
            ->return("/deps/d4")
            ->return("/deps/d4")
            ->fake("getContent")
            ->return(["###"])
            ->fake("onCopy")
            ->return(true)
            ->repeat(1)
            ->fake("onDownload")
            ->return(true);

        $box->fake("get")
            ->expect(class: Content::class, arguments: ["content" => ["###"]])
            ->return($content)
            ->repeat(3);

        $directory->fake("getStateDir")
            ->return("/tmp/state")
            ->fake("getPackagesDir")
            ->return("/tmp/packages")
            ->fake("createDir")
            ->expect(dir: "/tmp/state") // i0
            ->expect(dir: "/tmp/state/deps/d2")
            ->expect(dir: "/tmp/state/deps/d3")
            ->expect(dir: "/tmp/state/deps/d4")
            ->fake("rename")
            ->expect(from: "/tmp/packages/i0", to: "/tmp/state")
            ->expect(from: "/tmp/packages/i2", to: "/tmp/state/deps/d2")
            ->expect(from: "/tmp/packages/i3", to: "/tmp/state/deps/d3")
            ->expect(from: "/tmp/packages/i4", to: "/tmp/state/deps/d4");

        $task->execute();
    }
}