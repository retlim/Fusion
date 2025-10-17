<?php
/**
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

namespace Valvoid\Fusion\Tests\Tasks\Categorize;

use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;
use Valvoid\Fusion\Tasks\Categorize\Categorize;
use Valvoid\Fusion\Tasks\Categorize\Config\Normalizer;
use Valvoid\Fusion\Tests\Tasks\Categorize\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Categorize\Mocks\ExternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Categorize\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Categorize\Mocks\InternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Categorize\Mocks\LogMock;
use Valvoid\Fusion\Tests\Test;

class CategorizeTest extends Test
{
    protected string|array $coverage = [
        Categorize::class,

        // ballast
        Normalizer::class
    ];

    private BoxMock $box;

    public function __construct()
    {
        $this->box = new BoxMock;

        $this->testEfficientCategorization();
        $this->testRedundantCategorization();

        $this->box::unsetInstance();
    }

    public function testEfficientCategorization(): void
    {
        $group = new GroupMock;
        $categorize = new Categorize(
            box: $this->box,
            group: $group,
            log: new LogMock,
            config: ["efficiently" => true]
        );

        $group->internalMetas["i0"] = new InternalMetadataMock([
            "id" => "i0",
            "dir" => "/d0",
            "version" => "1.0.0"
        ]);

        $group->internalMetas["i1"] = new InternalMetadataMock([
            "id" => "i1",
            "dir" => "/d1",
            "version" => "1.0.0"
        ]);

        $group->externalMetas["i0"] = new ExternalMetadataMock([
            "id" => "i0",
            "dir" => "/d0",
            "version" => "1.0.1"
        ]);

        $group->externalMetas["i1"] = new ExternalMetadataMock([
            "id" => "i1",
            "dir" => "/d1/whatever",
            "version" => "1.0.0"
        ]);


        $categorize->execute();

        foreach ($group->getInternalMetas() as $metadata) {

            // assert diff version drop
            if ($metadata->getId() == "i0" &&
                $metadata->getCategory() === InternalCategory::OBSOLETE)
                continue;

            // assert diff dir recycling
            if ($metadata->getId() == "i1" &&
                $metadata->getCategory() === InternalCategory::MOVABLE)
                continue;

            $this->handleFailedTest();

            return;
        }

        foreach ($group->getExternalMetas() as $metadata) {

            // assert diff version download
            if ($metadata->getId() == "i0" &&
                $metadata->getCategory() === ExternalCategory::DOWNLOADABLE)
                continue;

            // assert diff dir drop
            if ($metadata->getId() == "i1" &&
                $metadata->getCategory() === ExternalCategory::REDUNDANT)
                continue;

            $this->handleFailedTest();

            return;
        }
    }

    public function testRedundantCategorization(): void
    {
        $group = new GroupMock;
        $categorize = new Categorize(
            box: $this->box,
            group: $group,
            log: new LogMock,
            config: ["efficiently" => false]
        );

        $group->internalMetas["i0"] = new InternalMetadataMock([
            "version" => "1.0.0"
        ]);

        $group->internalMetas["i1"] = new InternalMetadataMock([
            "version" => "1.0.0"
        ]);

        $group->externalMetas["i0"] = new ExternalMetadataMock([
            "dir" => "",
            "version" => "1.0.1"
        ]);

        $group->externalMetas["i1"] = new ExternalMetadataMock([
            "dir" => "/whatever",
            "version" => "1.0.0"
        ]);

        $categorize->execute();

        // assert internal drop
        foreach ($group->getInternalMetas() as $metadata)
            if ($metadata->getCategory() !== InternalCategory::OBSOLETE) {
                $this->handleFailedTest();

                return;
            }

        // assert new external state
        foreach ($group->getExternalMetas() as $metadata)
            if ($metadata->getCategory() !== ExternalCategory::DOWNLOADABLE) {
                $this->handleFailedTest();

                return;
            }
    }
}