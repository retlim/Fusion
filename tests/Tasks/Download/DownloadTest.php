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

namespace Valvoid\Fusion\Tests\Tasks\Download;

use Exception;
use Valvoid\Fusion\Container\Proxy\Logic;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Tasks\Download\Download;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tests\Tasks\Download\Mocks\DirMock;
use Valvoid\Fusion\Tests\Tasks\Download\Mocks\HubMock;
use Valvoid\Fusion\Tests\Tasks\Download\Mocks\ContainerMock;
use Valvoid\Fusion\Tests\Tasks\Download\Mocks\MetadataMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Integration test case for the download task.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class DownloadTest extends Test
{
    protected string|array $coverage = Download::class;

    private string $cache = __DIR__ . "/Mocks/package/cache/packages";

    public function __construct()
    {
        try {
            $this->delete($this->cache);

            $containerMock = new ContainerMock;
            $dir = new DirMock;
            $hub = new HubMock;
            (new Logic)->get(Group::class);
            MetadataMock::addMockedMetadata();

            $this->testTargetCacheDirectory();

            (new Logic)->unset(Group::class);
            $hub->destroy();
            $containerMock->destroy();
            $dir->destroy();

        } catch (Exception $exception) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;


                $containerMock->destroy();


                $dir->destroy();


                $hub->destroy();

            $this->result = false;
        }
    }

    /**
     * @throws Error
     */
    public function testTargetCacheDirectory(): void
    {
        $download = new Download(["id" => "test"]);
        $download->execute();

        if (is_dir($this->cache)) {
            $filenames = $this->getFilenames($this->cache);

            $assert = [
                __DIR__ . "/Mocks/package/cache/packages/metadata1",
                __DIR__ . "/Mocks/package/cache/packages/metadata1/fusion.bot.php",
                __DIR__ . "/Mocks/package/cache/packages/metadata1/fusion.json"
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