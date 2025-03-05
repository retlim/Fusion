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

namespace Valvoid\Fusion\Tests\Tasks\Shift;

use Exception;
use ReflectionClass;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Container\Container;
use Valvoid\Fusion\Container\Proxy\Logic;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tasks\Shift\Shift;
use Valvoid\Fusion\Tests\Tasks\Shift\Mocks\BusMock;
use Valvoid\Fusion\Tests\Tasks\Shift\Mocks\DirMock;
use Valvoid\Fusion\Tests\Tasks\Shift\Mocks\LogMock;
use Valvoid\Fusion\Tests\Tasks\Shift\Mocks\MetadataMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Integration test case for the shift task.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ShiftTest extends Test
{
    protected string|array $coverage = Shift::class;

    private string $cache = __DIR__ . '/cache';

    public function __construct()
    {
        try {
            $log = new LogMock;
            $bus = (new Logic)->get(Bus::class);
            $dir = new DirMock;
            $group = (new Logic)->get(Group::class);

            // new root version
            $this->testShiftRecursive();
            $group->destroy();
            $dir->destroy();

            $dir = new DirMock;
            $group = (new Logic)->get(Group::class);

            // new root with new cache dir
            $this->testShiftRecursiveCache();
            $group->destroy(); // clear
            $dir->destroy();

            $dir = new DirMock;
            $group = (new Logic)->get(Group::class);

            $this->testShiftNested();
            $group->destroy(); // clear
            $dir->destroy();

            $dir = new DirMock;
            $group = (new Logic)->get(Group::class);

            // check if persisted inside "other" dir
            $this->testShiftRecursiveWithExecutedFiles();
            $group->destroy(); // clear
            $dir->destroy();

            $dir = new DirMock;
            $group = (new Logic)->get(Group::class);

            $this->testShiftNestedWithExecutedFiles();
            $group->destroy();
            $log->destroy();
            $dir->destroy();
            $bus->destroy();

        } catch (Exception $exception) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            if (isset($group))
                $group->destroy();

                $log->destroy();

            if (isset($dir))
                $dir->destroy();

            if (isset($bus))
                $bus->destroy();

            $this->result = false;
        }
    }

    public function testShiftRecursive(): void
    {
        $this->setUp(__DIR__ . '/Mocks/package/recursive');
        MetadataMock::addRecursive();
        (new Shift([]))->execute();

        if (!file_exists("$this->cache/new") ||
            !file_exists("$this->cache/cache/new") ||
            !file_exists("$this->cache/cache/log/keep")) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testShiftRecursiveCache(): void
    {
        $this->setUp(__DIR__ . '/Mocks/package/cache');
        MetadataMock::addRecursiveCache();
        (new Shift([]))->execute();

        if (file_exists("$this->cache/cache") || // old cache dir
            !file_exists("$this->cache/new") ||
            !file_exists("$this->cache/che/new") ||
            !file_exists("$this->cache/che/log/keep")) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testShiftNested(): void
    {
        $this->setUp(__DIR__ . '/Mocks/package/nested');
        MetadataMock::addNested();
        (new Shift([]))->execute();

        if (!file_exists("$this->cache/old") ||
            !file_exists("$this->cache/dependencies/metadata3/new") ||
            !file_exists("$this->cache/cache/new") ||
            !file_exists("$this->cache/cache/log/keep")) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }

    }

    public function testShiftRecursiveWithExecutedFiles(): void
    {
        $this->setUp(__DIR__ . '/Mocks/package/recursive_executed');
        MetadataMock::addRecursiveExecuted();

        $reflection = new ReflectionClass(Shift::class);
        $task = $reflection->newInstance([]);
        $reflection->getProperty("dir")->setValue($task, __DIR__ . '/cache');

        $handle = fopen("$this->cache/fusion", 'r');
        $other = "$this->cache/cache/other/valvoid/fusion";

        if ($handle !== false &&
            fgets($handle) == "old") {
            $task->execute();

            if (rewind($handle) !== false &&
                fgets($handle) == "new" &&
                fclose($handle) !== false &&
                file_exists("$this->cache/new") &&
                file_exists("$this->cache/cache/log/keep") &&
                file_exists("$this->cache/cache/new") &&

                // persisted session
                file_exists("$other/old") &&
                file_exists("$other/fusion") &&
                file_get_contents("$other/fusion") == "old" &&
                file_exists("$other/cache/log/keep") &&
                file_exists("$other/cache/old")) {

                return;
            }
        }

        echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

        $this->result = false;
    }

    public function testShiftNestedWithExecutedFiles(): void
    {
        $this->setUp(__DIR__ . '/Mocks/package/nested_executed');
        MetadataMock::addNestedExecuted();

        $reflection = new ReflectionClass(Shift::class);
        $task = $reflection->newInstance([]);
        $reflection->getProperty("dir")->setValue($task, __DIR__ . '/cache');

        $handle = fopen("$this->cache/dependencies/valvoid/fusion/fusion", 'r');
        $other = "$this->cache/cache/other/valvoid/fusion";

        if ($handle !== false &&
            fgets($handle) == "old") {
            $task->execute();

            if (rewind($handle) !== false &&
                fgets($handle) == "new" &&
                fclose($handle) !== false &&
                file_exists("$this->cache/old") &&
                file_exists("$this->cache/cache/log/keep") &&
                file_exists("$this->cache/cache/new") &&

                // persisted session
                file_exists("$other/old") &&
                file_exists("$other/fusion") &&
                file_get_contents("$other/fusion") == "old" &&
                file_exists("$other/cache/old")) {

                return;
            }
        }

        echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

        $this->result = false;
    }

    private function setUp(string $dir): void
    {
        $this->delete($this->cache);

        if (!mkdir($this->cache, 0755))
            throw new Exception(
                "Can't create the directory \"cache\"."
            );


        $this->copy($dir, $this->cache);
    }


    private function delete(string $file): void
    {
        if (is_dir($file)) {
            foreach (scandir($file, SCANDIR_SORT_NONE) as $filename)
                if ($filename != "." && $filename != "..")
                    $this->delete("$file/$filename");

            rmdir($file);

        } elseif (is_file($file))
            unlink($file);
    }

    /**
     * @throws Exception
     */
    private function copy(string $from, string $to): void
    {
        foreach (scandir($from, SCANDIR_SORT_NONE) as $filename)
            if ($filename != "." && $filename != "..") {
                $file = "$from/$filename";
                $copy = "$to/$filename";

                if (is_file($file)) {
                    if (!copy($file, $copy))
                        throw new Exception(
                            "Can't copy the file \"$file\" to \"$copy\"."
                        );

                } else {
                    if (!mkdir($copy, 0755, true))
                        throw new Exception(
                            "Can't create the directory \"$copy\"."
                        );

                    $this->copy($file, $copy);
                }
            }
    }
}