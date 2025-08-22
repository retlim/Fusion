<?php
/**
 * Fusion. A package manager for PHP-based projects.
 * Copyright Valvoid
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
 */

namespace Valvoid\Fusion\Tests\Tasks\Categorize;

use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;
use Valvoid\Fusion\Tasks\Categorize\Categorize;
use Valvoid\Fusion\Tests\Tasks\Categorize\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Categorize\Mocks\BusMock;
use Valvoid\Fusion\Tests\Tasks\Categorize\Mocks\ExternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Categorize\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Categorize\Mocks\InternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Categorize\Mocks\LogMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Test case for the categorize task.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class CategorizeTest extends Test
{
    protected string|array $coverage = Categorize::class;
    private GroupMock $group;

    public function __construct()
    {
        $this->group = new GroupMock;
        $box = new BoxMock;
        $box->bus = new BusMock;
        $box->group = $this->group;
        $box->log = new LogMock;

        $this->group->internalMetas["metadata1"] = new InternalMetadataMock([
            "id" => "metadata1",
            "name" => "metadata1",
            "description" => "metadata1",
            "source" => "metadata1",
            "dir" => __DIR__,
            "version" => "1.0.0"
        ]);

        $this->group->internalMetas["metadata2"] = new InternalMetadataMock([
            "id" => "metadata2",
            "name" => "metadata2",
            "description" => "metadata2",
            "source" => "metadata2",
            "dir" => __DIR__,
            "version" => "1.0.0"
        ]);

        $this->group->externalMetas["metadata1"] = new ExternalMetadataMock([
            "id" => "metadata1",
            "name" => "metadata1",
            "description" => "metadata1",
            "source" => "metadata1",
            "dir" => __DIR__,
            "version" => "1.0.1"
        ]);

        $this->group->externalMetas["metadata2"] = new ExternalMetadataMock([
            "id" => "metadata2",
            "name" => "metadata2",
            "description" => "metadata2",
            "source" => "metadata2",
            "dir" => __DIR__ . "/whatever",
            "version" => "1.0.0"
        ]);

        $this->testEfficientCategorization();
        $this->testRedundantCategorization();

        $box::unsetInstance();
    }

    public function testEfficientCategorization(): void
    {
        // recycle
        $categorize = new Categorize(["efficiently" => true]);

        $categorize->execute();

        foreach ($this->group->getInternalMetas() as $metadata) {

            // assert diff version drop
            if ($metadata->getId() == "metadata1" &&
                $metadata->getCategory() === InternalCategory::OBSOLETE)
                continue;

            // assert diff dir recycling
            if ($metadata->getId() == "metadata2" &&
                $metadata->getCategory() === InternalCategory::MOVABLE)
                continue;

            $this->handleFailedTest();

            return;
        }

        foreach ($this->group->getExternalMetas() as $metadata) {

            // assert diff version download
            if ($metadata->getId() == "metadata1" &&
                $metadata->getCategory() === ExternalCategory::DOWNLOADABLE)
                continue;

            // assert diff dir drop
            if ($metadata->getId() == "metadata2" &&
                $metadata->getCategory() === ExternalCategory::REDUNDANT)
                continue;

            $this->handleFailedTest();

            return;
        }
    }

    public function testRedundantCategorization(): void
    {
        // rebuild all
        $categorize = new Categorize(["efficiently" => false]);

        $categorize->execute();

        // assert internal drop
        foreach ($this->group->getInternalMetas() as $metadata)
            if ($metadata->getCategory() !== InternalCategory::OBSOLETE) {
                $this->handleFailedTest();

                return;
            }

        // assert new external state
        foreach ($this->group->getExternalMetas() as $metadata)
            if ($metadata->getCategory() !== ExternalCategory::DOWNLOADABLE) {
                $this->handleFailedTest();

                return;
            }
    }
}