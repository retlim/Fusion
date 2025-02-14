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

namespace Valvoid\Fusion\Tests\Tasks\Snap;

use Exception;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tasks\Snap\Snap;
use Valvoid\Fusion\Tests\Tasks\Snap\Mocks\DirMock;
use Valvoid\Fusion\Tests\Tasks\Snap\Mocks\LogMock;
use Valvoid\Fusion\Tests\Tasks\Snap\Mocks\MetadataMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Integration test case for the snap task.
 *
 * @Copyright Valvoid
 * @license GNU GPLv3
 */
class SnapTest extends Test
{
    protected string|array $coverage = Snap::class;

    public function __construct()
    {
        try {
            $this->delete(__DIR__ . "/Mocks/package");

            $log = new LogMock;
            $dir = new DirMock;
            $group = Group::___init();

            MetadataMock::addRedundantMockedMetadata();
            $this->testRedundantCacheRefresh();
            $group->destroy();

            $group = Group::___init();

            MetadataMock::addDownloadableMockedMetadata();
            $this->testDownloadableCacheUpdate();

            $group->destroy();
            $log->destroy();
            $dir->destroy();

        } catch (Exception $exception) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            if (isset($group))
                $group->destroy();

            if (isset($log))
                $log->destroy();

            if (isset($dir))
                $dir->destroy();

            $this->result = false;
        }
    }

    /**
     * @throws Error
     */
    public function testRedundantCacheRefresh(): void
    {
        $snap = new Snap([]);
        $snap->execute();

        $snapshot = file_get_contents(__DIR__ . "/Mocks/package/cache/snapshot.json");

        if ($snapshot) {
            $snapshot = json_decode($snapshot, true);

            // no recursive root
            // offset
            if (["metadata2" => "3.2.1:offset", "metadata3" => "1.2.3"] === $snapshot)
                return;
        }

        echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

        $this->result = false;
    }

    /**
     * @throws Error
     */
    public function testDownloadableCacheUpdate(): void
    {
        $prefix = __DIR__ . "/Mocks/package/cache/packages/metadata1/cache";
        $snap = new Snap([]);
        $snap->execute();

        // no local metadata
        // fusion.local.php is null
        if (!file_exists("$prefix/snapshot.local.json")) {
            $snapshot = file_get_contents("$prefix/snapshot.json");
            $devSnapshot = file_get_contents("$prefix/snapshot.dev.json");

            if ($snapshot && $devSnapshot) {
                $snapshot = json_decode($snapshot, true);
                $devSnapshot = json_decode($devSnapshot, true);

                // keep order but actually no matter
                if (["metadata3" => "5.4.3:offset", "metadata2" => "6.7.8"] === $snapshot &&

                    // existing fusion.dev.php contains no deps
                    $devSnapshot === [] )
                    return;
            }
        }

       echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

       $this->result = false;
    }

    /**
     * @param string $file
     * @return void
     */
    public function delete(string $file): void
    {
        if (is_dir($file)) {
            foreach (scandir($file, SCANDIR_SORT_NONE) as $filename)
                if ($filename != "." && $filename != "..")
                    $this->delete("$file/$filename");

            rmdir($file);

        } elseif (is_file($file))
            unlink($file);
    }
}