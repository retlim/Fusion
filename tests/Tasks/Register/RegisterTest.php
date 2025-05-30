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

namespace Valvoid\Fusion\Tests\Tasks\Register;

use Exception;
use Valvoid\Fusion\Tasks\Register\Register;
use Valvoid\Fusion\Tests\Tasks\Register\Mocks\ContainerMock;
use Valvoid\Fusion\Tests\Tasks\Register\Mocks\MetadataMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Integration test case for the register task.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class RegisterTest extends Test
{
    protected string|array $coverage = Register::class;

    private int $time;

    public function __construct()
    {
        try {
            $this->time = time();
            $containerMock = new ContainerMock;
            $task = new Register([]);

            MetadataMock::addRefreshMetadata();

            $task->execute();
            $this->testRefreshAutoloader();

            unset($containerMock->logic->group);

            MetadataMock::addNewStateMetadata();
            $task = new Register([]);

            $task->execute();
            $this->testNewStateAutoloader();
            $containerMock->destroy();

        } catch (Exception $exception) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;
            echo "\n " . $exception->getMessage();


                $containerMock->destroy();

            $this->result = false;
        }
    }

    public function testRefreshAutoloader(): void
    {
        $autoloader = __DIR__ . "/Mocks/package/cache/Autoloader.php";

        if (is_file($autoloader) && filemtime($autoloader) >= $this->time)
            return;

        echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

        $this->result = false;
    }

    public function testNewStateAutoloader(): void
    {
        $autoloader = __DIR__ . "/Mocks/package/cache/packages/metadata1/cache/Autoloader.php";

        if (is_file($autoloader) && filemtime($autoloader) >= $this->time)
            return;

        echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

        $this->result = false;
    }
}