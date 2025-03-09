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

namespace Valvoid\Fusion\Tests\Group;

use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tests\Group\Mocks\ContainerMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Test case for the task group.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class GroupTest extends Test
{
    protected string|array $coverage = Group::class;
    private ContainerMock $container;

    public function __construct()
    {
        $this->container = new ContainerMock;

        // static
        $this->testStaticInterface();
        $this->container->destroy();
    }

    public function testStaticInterface(): void
    {
        Group::setInternalMetas([]);
        Group::setImplication([]);
        Group::setExternalMetas([]);
        Group::getExternalRootMetadata();
        Group::getInternalRootMetadata();
        Group::getRootMetadata();
        Group::hasDownloadable();
        Group::getExternalMetas();
        Group::getInternalMetas();
        Group::setImplicationBreadcrumb([]);
        Group::getImplication();
        Group::getPath("");
        Group::getSourcePath([],"");

        // static functions connected to same non-static functions
        if ($this->container->logic->group->calls !== [
                "setInternalMetas",
                "setImplication",
                "setExternalMetas",
                "getExternalRootMetadata",
                "getInternalRootMetadata",
                "getRootMetadata",
                "hasDownloadable",
                "getExternalMetas",
                "getInternalMetas",
                "setImplicationBreadcrumb",
                "getImplication",
                "getPath",
                "getSourcePath"]) {

            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}