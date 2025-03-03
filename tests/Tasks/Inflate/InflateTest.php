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

namespace Valvoid\Fusion\Tests\Tasks\Inflate;

use Exception;
use Valvoid\Fusion\Container\Container;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tasks\Inflate\Inflate;
use Valvoid\Fusion\Tests\Tasks\Inflate\Mocks\LogMock;
use Valvoid\Fusion\Tests\Tasks\Inflate\Mocks\DirMock;
use Valvoid\Fusion\Tests\Tasks\Inflate\Mocks\MetadataMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Integration test case for the inflate task.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class InflateTest extends Test
{
    protected string|array $coverage = Inflate::class;

    private string $cache = __DIR__ . "/Mocks/package/cache/packages";

    private string $dependencies = __DIR__ . "/Mocks/package/dependencies";

    private int $time;

    public function __construct()
    {
        try {
            $this->time = time();
            $dir = new DirMock;
            $log = new LogMock;
            $group = Container::get(Group::class);
            $task = new Inflate([]);

            MetadataMock::addRefreshMetadata();

            $task->execute();
            $this->testRefreshClassAndFunction();
            $this->testRefreshInterface();
            $this->testRefreshTrait();
            $group->destroy();

            $group = Container::get(Group::class);

            MetadataMock::addNewStateMetadata();
            $task = new Inflate([]);

            $task->execute();
            $this->testNewStateFinalClass();
            $this->testNewStateAbstractClass();
            $this->testNewStateEnum();
            $group->destroy();
            $log->destroy();
            $dir->destroy();

        } catch (Exception $exception) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            if (isset($group))
                $group->destroy();


                $dir->destroy();


                $log->destroy();

            $this->result = false;
        }
    }

    public function testRefreshClassAndFunction(): void
    {
        $loadable = __DIR__ . "/Mocks/package/cache/loadable";
        $asap = "$loadable/asap.php";
        $lazy = "$loadable/lazy.php";

        if (is_file($asap) && is_file($lazy) &&
            filemtime($asap) >= $this->time && filemtime($lazy) >= $this->time) {
            $asap = include "$loadable/asap.php";
            $lazy = include "$loadable/lazy.php";

            if ($lazy == ['Metadata1\Metadata1' => '/Metadata1.php'] &&
                $asap == ["/metadata_1.php"])
                return;
        }

        echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

        $this->result = false;
    }

    public function testRefreshInterface(): void
    {
        $loadable = "$this->dependencies/metadata2/cache/loadable";
        $lazy = "$loadable/lazy.php";

        if (is_file($lazy) && filemtime($lazy) >= $this->time) {
            $lazy = include "$loadable/lazy.php";

            if ($lazy == ['Metadata2\Whatever\Metadata2' => '/Metadata2.php'])
                return;
        }

        echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

        $this->result = false;
    }

    public function testRefreshTrait(): void
    {
        $loadable = "$this->dependencies/metadata3/cache/loadable";
        $lazy = "$loadable/lazy.php";

        if (is_file($lazy) && filemtime($lazy) >= $this->time) {
            $lazy = include "$loadable/lazy.php";

            if ($lazy == ['Metadata3\Metadata3' => '/Metadata3.php'])
                return;
        }

        echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

        $this->result = false;
    }

    public function testNewStateFinalClass(): void
    {
        $loadable = "$this->cache/metadata1/cache/loadable";
        $lazy = "$loadable/lazy.php";

        if (is_file($lazy) && filemtime($lazy) >= $this->time) {
            $lazy = include "$loadable/lazy.php";

            if ($lazy == ['Metadata1\Final\Metadata1' => '/Metadata1.php'])
                return;
        }

        echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

        $this->result = false;
    }

    public function testNewStateAbstractClass(): void
    {
        $loadable = "$this->cache/metadata2/cache/loadable";
        $lazy = "$loadable/lazy.php";

        if (is_file($lazy) && filemtime($lazy) >= $this->time) {
            $lazy = include "$loadable/lazy.php";

            if ($lazy == ['Metadata2\Metadata2' => '/Metadata2.php'])
                return;
        }

        echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

        $this->result = false;
    }

    public function testNewStateEnum(): void
    {
        $loadable = "$this->cache/metadata3/cache/loadable";
        $lazy = "$loadable/lazy.php";

        if (is_file($lazy) && filemtime($lazy) >= $this->time) {
            $lazy = include "$loadable/lazy.php";

            if ($lazy == ['Metadata3\Enum\Metadata3' => '/Metadata3.php'])
                return;
        }

        echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

        $this->result = false;
    }
}