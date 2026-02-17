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

namespace Valvoid\Fusion\Tests\Units\Tasks;

use Valvoid\Fusion\Metadata\External\Category;
use Valvoid\Fusion\Metadata\External\External;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Reflex\Test\Wrapper;

class GroupTest extends Wrapper
{
    public function testMetadataTypes(): void
    {
        $external = $this->createStub(External::class);
        $internal = $this->createStub(Internal::class);
        $group = new Group;

        // root metadata
        // no/empty dir relative to project root
        $internal->fake("getDir")
            ->return("");

        // overrides internal
        // remote source
        $external->fake("getDir")
            ->return("");

        $group->setInternalMetas([$internal]);
        $this->validate($group->getInternalMetas())
            ->as([$internal]);

        $group->setExternalMetas([$external]);
        $this->validate($group->getExternalMetas())
            ->as([$external]);
    }

    public function testInternalRootMetadata(): void
    {
        $internal = $this->recycleStub(Internal::class);
        $group = new Group;

        $group->setInternalMetas([$internal]);
        $this->validate($group->getRootMetadata())
            ->as($internal);

        $this->validate($group->getInternalRootMetadata())
            ->as($internal);
    }

    public function testExternalRootMetadata(): void
    {
        $external = $this->recycleStub(External::class);
        $internal = $this->recycleStub(Internal::class);
        $group = new Group;

        $group->setInternalMetas([$internal]);
        $group->setExternalMetas([$external]);
        $this->validate($group->getRootMetadata())
            ->as($external);

        $this->validate($group->getExternalRootMetadata())
            ->as($external);
    }

    public function testDownloadable(): void
    {
        $external = $this->recycleStub(External::class);
        $group = new Group;

        $this->validate($group->hasDownloadable())
            ->as(false);

        $external->fake("getCategory")
            ->return(Category::DOWNLOADABLE);

        $group->setExternalMetas([$external]);
        $this->validate($group->hasDownloadable())
            ->as(true);
    }

    public function testSourceTrace(): void
    {
        $group = new Group;
        $implication = [
            "#id0"=> [
                "source" => "#s0",
                "implication" => [
                    "#id1" => [
                        "source" => "#s1",
                        "implication" => []
                    ]
                ]
            ],
            "#id2"=> [
                "source" => "#s2",
                "implication" => []
            ]
        ];

        $this->validate($group->getSourceTrace($implication, "###"))
            ->as([]);

        $this->validate($group->getSourceTrace($implication, "#s1"))
            ->as([
                "#id0" => "#s0",
                "#id1" => "#s1"
            ]);
    }
}