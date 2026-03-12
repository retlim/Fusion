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

namespace Valvoid\Fusion\Tests\Units\Tasks\Categorize;

use Valvoid\Box\Box;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Metadata\External\External;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;
use Valvoid\Fusion\Tasks\Categorize\Categorize;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Reflex\Test\Wrapper;

class CategorizeTest extends Wrapper
{
    public function testEfficientCategorization(): void
    {
        $box = $this->createMock(Box::class);
        $group = $this->createMock(Group::class);
        $log = $this->createStub(Log::class);
        $content = $this->createStub(Content::class);
        $internal = $this->createMock(Internal::class);
        $external = $this->createMock(External::class);
        $categorize = new Categorize(
            box: $box,
            group: $group,
            log: $log,
            config: ["efficiently" => true]
        );

        $box->fake("get")
            ->expect(class: Content::class)
            ->return($content)
            ->repeat(2);

        $log->fake("info")
            ->return(null)
            ->repeat(7);

        $group->fake("getInternalMetas")
            ->return([
                "i0" => $internal,
                "i1" => $internal
            ]);

        $group->fake("getExternalMetas")
            ->return([
                "i0" => $external,
                "i1" => $external
            ]);

        $internal->fake("getDir")
            ->return("/d0")
            ->fake("getVersion")
            ->return("1.0.0")
            ->return("1.0.0")
            ->fake("setCategory")
            ->expect(category: InternalCategory::OBSOLETE)
            ->expect(category: InternalCategory::MOVABLE)
            ->fake("getContent")
            ->return(["###"])
            ->repeat(1);

        $external->fake("getDir")
            ->return("/d0")
            ->return("/d1/whatever")
            ->fake("getVersion")
            ->return("1.0.1")
            ->return("1.0.0")
            ->fake("setCategory")
            ->fake("setCategory")
            ->expect(category: ExternalCategory::DOWNLOADABLE)
            ->expect(category: ExternalCategory::REDUNDANT)
            ->fake("getContent")
            ->return(["###"]);

        $categorize->execute();
    }

    public function testRedundantCategorization(): void
    {
        $box = $this->createMock(Box::class);
        $group = $this->createMock(Group::class);
        $log = $this->createStub(Log::class);
        $content = $this->createStub(Content::class);
        $internal = $this->createMock(Internal::class);
        $external = $this->createMock(External::class);
        $categorize = new Categorize(
            box: $box,
            group: $group,
            log: $log,
            config: ["efficiently" => false]
        );

        $box->fake("get")
            ->expect(class: Content::class)
            ->return($content)
            ->repeat(3);

        $log->fake("info")
            ->return(null)
            ->repeat(7);

        $group->fake("getInternalMetas")
            ->return([
                "i0" => $internal,
                "i1" => $internal
            ]);

        $group->fake("getExternalMetas")
            ->return([
                "i0" => $external,
                "i1" => $external
            ]);

        $internal->fake("setCategory")
            ->expect(category: InternalCategory::OBSOLETE)
            ->expect(category: InternalCategory::OBSOLETE)
            ->fake("getContent")
            ->return(["###"])
            ->repeat(1);

        $external->fake("getDir")
            ->return("")
            ->return("/whatever")
            ->fake("setCategory")
            ->fake("setCategory")
            ->expect(category: ExternalCategory::DOWNLOADABLE)
            ->expect(category: ExternalCategory::DOWNLOADABLE)
            ->fake("getContent")
            ->return(["###"])
            ->repeat(1);

        $categorize->execute();
    }
}