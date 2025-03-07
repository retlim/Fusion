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

namespace Valvoid\Fusion\Tests\Tasks\Copy;

use Exception;
use Valvoid\Fusion\Container\Proxy\Logic;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Tasks\Copy\Copy;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tests\Tasks\Copy\Mocks\DirMock;
use Valvoid\Fusion\Tests\Tasks\Copy\Mocks\LogMock;
use Valvoid\Fusion\Tests\Tasks\Copy\Mocks\MetadataMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Integration test case for the copy task.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class CopyTest extends Test
{
    protected string|array $coverage = Copy::class;

    private string $cache = __DIR__ . "/Mocks/package/cache/packages";

    public function __construct()
    {
        try {
            $log = new LogMock;
            $dir = new DirMock;
            $group = (new Logic)->get(Group::class);
            MetadataMock::addMockedMetadata();

            $this->testTargetCacheDirectory();

            $group->destroy();
            $log->destroy();
            $dir->destroy();

        } catch (Exception $exception) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            if (isset($group))
                $group->destroy();

            $log->destroy();
            $dir->destroy();

            $this->result = false;
        }
    }

    /**
     * @throws Error
     */
    public function testTargetCacheDirectory(): void
    {
        $copy = new Copy([]);
        $copy->execute();

        if (is_dir($this->cache)) {
            $filenames = $this->getFilenames($this->cache);

            $assert = [
                __DIR__ . "/Mocks/package/cache/packages/metadata1",
                __DIR__ . "/Mocks/package/cache/packages/metadata1/metadata1",
                __DIR__ . "/Mocks/package/cache/packages/metadata2",
                __DIR__ . "/Mocks/package/cache/packages/metadata2/metadata2"
            ];

            if ($filenames == $assert)
                return;
        }

        echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

        $this->result = false;
    }

    private function getFilenames(string $dir): array
    {
        $content = [];

        if (is_dir($dir)) {
            $filenames = scandir($dir);

            if ($filenames !== false) {
                foreach ($filenames as $filename) {
                    if ($filename === '.' || $filename === '..')
                        continue;

                    $file = $dir . '/' . $filename;
                    $content[] = $file;

                    if (is_dir($file))
                        $content = array_merge($content, $this->getFilenames($file));
                }
            }
        }

        return $content;
    }
}