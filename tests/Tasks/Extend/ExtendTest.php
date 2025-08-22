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

namespace Valvoid\Fusion\Tests\Tasks\Extend;

use Exception;
use Valvoid\Fusion\Tasks\Extend\Extend;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\BusMock;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\ExternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\InternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Extend\Mocks\LogMock;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Tests\Test;

/**
 * Integration test case for the extend task.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ExtendTest extends Test
{
    protected string|array $coverage = Extend::class;

    private string $cache = __DIR__ . "/Mocks/package/cache/packages";

    private string $dir = __DIR__ . "/Mocks/package/dependencies/metadata2/extensions";

    private array $structure = [
        __DIR__ . "/Mocks/package/cache/packages/metadata1",
        __DIR__ . "/Mocks/package/cache/packages/metadata2/extensions/metadata1",
        __DIR__ . "/Mocks/package/cache/packages/metadata2/extensions/metadata2",
        __DIR__ . "/Mocks/package/cache/packages/metadata3",
        __DIR__ . "/Mocks/package/dependencies/metadata2/extensions/metadata1",
        __DIR__ . "/Mocks/package/dependencies/metadata2/extensions/metadata2",
    ];

    private int $time;

    public function __construct()
    {
        $box = new BoxMock;
        $box->bus = new BusMock;
        $box->log = new LogMock;
        $group = new GroupMock;
        $box->group = $group;
        $group->hasDownloadable = false;
        try {


            foreach ($this->structure as $directory)
                if (!is_dir($directory))
                    if (!mkdir($directory, 0777, true))
                        throw new Exception(
                            "Failed to create structure directory $directory"
                        );

            $this->time = time();
            $ballast = "$this->dir/metadata3";
            $task = new Extend([]);

            // refresh state
            // implication contains only existing versions
            // refresh cache and extensions dirs
            $group->implication = ["metadata2" => [ // no external root
                "implication" => []
            ]];
            $group->internalMetas["metadata1"] = new InternalMetadataMock([
                "id" => "metadata1",
                "name" => "metadata1",
                "description" => "metadata1",
                "source" => __DIR__ . "/Mocks/package",
                "dir" => "", // relative to root dir
                "version" => "1.0.0",
                "structure" => [
                    "cache" => "/cache",
                    "extensions" => [],
                    "sources" => [
                        "/dependencies" => []
                    ]
                ]
            ]);

            $group->internalRoot = $group->internalMetas["metadata1"];
            $group->internalMetas["metadata2"] = new InternalMetadataMock([
                "id" => "metadata2",
                "name" => "metadata2",
                "description" => "metadata2",
                "source" => __DIR__ . "/Mocks/package/dependencies/metadata2",
                "dir" => "/dependencies/metadata2", // relative to root dir
                "version" => "1.0.0",
                "structure" => [
                    "cache" => "/cache",
                    "extensions" => [
                        "/extensions"
                    ],
                    "sources" => []
                ]
            ]);

            if (!file_exists($ballast) && !mkdir($ballast, 0777, true))
                throw new Exception(
                    "Cannot create directory \"$ballast\""
                );

            $task->execute();
            $this->testRefreshExtensionsFiles();
            $this->testRefreshExtensionsOrder();
            $this->testRefreshBallast();

            // clear previous metadata
            $group = new GroupMock;
            $box->group = $group;
            $group->hasDownloadable = true;

            $from = "$this->cache/metadata3/dependencies/metadata2/extensions/metadata3";
            $to = "$this->cache/metadata2/extensions/metadata3";
            $ballast = "$this->cache/metadata2/extensions/metadata6";
            $task = new Extend([]);

            // new state
            // implication contains at least one downloadable (metadata3) package
            // loop packages inside cached state
            $group->internalMetas = [
                "metadata1" => new InternalMetadataMock([
                    "id" => "metadata1",
                    "name" => "metadata1",
                    "description" => "metadata1",
                    "source" => __DIR__ . "/Mocks/package",
                    "dir" => "", // relative to root dir
                    "version" => "1.0.0",
                    "structure" => [
                        "cache" => "/cache",
                        "extensions" => [],
                        "sources" => [
                            "/dependencies" => []
                        ]
                    ]
                ])
            ];

            $group->internalRoot = $group->internalMetas["metadata1"];
            $group->implication = [
                "metadata1" => [
                    "implication" => [
                        "metadata2" => [
                            "implication" => []
                        ],
                        "metadata3" => [
                            "implication" => [
                                "metadata2" => [
                                    "implication" => []
                                ]
                            ]
                        ],
                    ]
                ]
            ];

            $group->externalMetas["metadata1"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE, [
                    "id" => "metadata1",
                    "name" => "metadata1",
                    "description" => "metadata1",
                    "source" => "/package",
                    "dir" => "", // relative to root dir
                    "version" => "1.0.0",
                    "structure" => [
                        "cache" => "/cache",
                        "extensions" => [],
                        "sources" => [
                            "/dependencies" => [
                                "metadata2",
                                "metadata3"
                            ]
                        ]
                    ]
                ]);

            $group->externalMetas["metadata2"] = new ExternalMetadataMock(
                ExternalCategory::REDUNDANT, [
                "id" => "metadata2",
                "name" => "metadata2",
                "description" => "metadata2",
                "source" => "/package/dependencies/metadata2",
                "dir" => "/dependencies/metadata2",
                "version" => "1.0.0",
                "structure" => [
                    "cache" => "/cache",
                    "extensions" => [
                        "/extensions"
                    ],
                    "sources" => []
                ]
            ]);

            $group->externalMetas["metadata3"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE, [
                "id" => "metadata3",
                "name" => "metadata3",
                "description" => "metadata3",
                "source" => "whatever/metadata3",
                "dir" => "/dependencies/metadata3",
                "version" => "1.0.0",
                "structure" => [
                    "cache" => "/cache",
                    "extensions" => [],
                    "sources" => [
                        "/dependencies" => ["metadata2"]
                    ]
                ]
            ]);

            if (!file_exists($from) && !mkdir($from, 0777, true))
                throw new Exception(
                    "Cannot create from directory \"$from\""
                );

            if (is_dir($to) && !rmdir($to))
                throw new Exception(
                    "Cannot delete to directory \"$to\""
                );

            if (!file_exists($ballast) && !mkdir($ballast, 0777, true))
                throw new Exception(
                    "Cannot create ballast directory \"$ballast\""
                );

            $task->execute();
            $this->testNewStateExtensionsFiles();
            $this->testNewStateExtensionsOrder();
            $this->testNewStateBallast();
            $this->testNewStateExtension();
            $box::unsetInstance();

        } catch (Exception $exception) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;
            echo "\n " . $exception->getMessage();

            $this->result = false;
        }

        $box::unsetInstance();
    }

    public function testRefreshExtensionsFiles(): void
    {
        $file1 = __DIR__ . "/Mocks/package/cache/extensions.php";
        $file2 = __DIR__ . "/Mocks/package/dependencies/metadata2/cache/extensions.php";

        if (file_exists($file1) && filemtime($file1) >= $this->time &&
            file_exists($file2) && filemtime($file2) >= $this->time)
            return;

        $this->handleFailedTest();
    }

    public function testRefreshExtensionsOrder(): void
    {
        $file1 = __DIR__ . "/Mocks/package/cache/extensions.php";
        $file2 = __DIR__ . "/Mocks/package/dependencies/metadata2/cache/extensions.php";

        $content1 = include $file1;
        $content2 = include $file2;

        if ($content1 == [] && ($content2["/extensions"] ?? null) == [
                0 => "metadata2",
                1 => "metadata1"
            ])
            return;

        $this->handleFailedTest();
    }

    public function testRefreshBallast(): void
    {
        $filenames = $this->getFilenames($this->dir);
        $assertion = [
            "$this->dir/metadata1",
            "$this->dir/metadata2",
        ];

        if ($filenames == $assertion)
            return;

        $this->handleFailedTest();
    }

    public function testNewStateExtensionsFiles(): void
    {
        $file1 = "$this->cache/metadata1/cache/extensions.php";
        $file2 = "$this->cache/metadata2/cache/extensions.php";
        $file3 = "$this->cache/metadata3/cache/extensions.php";

        if (file_exists($file1) && filemtime($file1) >= $this->time &&
            file_exists($file2) && filemtime($file2) >= $this->time &&
            file_exists($file3) && filemtime($file3) >= $this->time)
            return;

        $this->handleFailedTest();
    }

    public function testNewStateExtensionsOrder(): void
    {
        $file1 = "$this->cache/metadata1/cache/extensions.php";
        $file2 = "$this->cache/metadata2/cache/extensions.php";
        $file3 = "$this->cache/metadata3/cache/extensions.php";

        $content1 = include $file1;
        $content2 = include $file2;
        $content3 = include $file3;

        if ($content1 == [] && $content3 == [] &&
            ($content2["/extensions"] ?? null) == [

                // top down order
                0 => "metadata2", // dep of meta1
                1 => "metadata2", // dep of meta3
                2 => "metadata3",
                3 => "metadata1",
                4 => "metadata1"
            ])
            return;

        $this->handleFailedTest();
    }

    public function testNewStateBallast(): void
    {
        if (!file_exists("$this->cache/metadata2/extensions/metadata6"))
            return;

        $this->handleFailedTest();
    }

    public function testNewStateExtension(): void
    {
        // moved extension to metadata2
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

    /**
     * @throws Exception
     */
    public function delete(string $file): void
    {
        if (is_dir($file)) {
            foreach (scandir($file, SCANDIR_SORT_NONE) as $filename)
                if ($filename != "." && $filename != "..")
                    $this->delete("$file/$filename");

            if (!rmdir($file))
                throw new Exception(
                    "Cannot delete directory \"$file\""
                );

        } elseif (is_file($file))
            if (!unlink($file))
                throw new Exception(
                    "Cannot delete file \"$file\""
                );
    }
}