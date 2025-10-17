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

namespace Valvoid\Fusion\Tests\Tasks\Download;

use Closure;
use Exception;
use Valvoid\Fusion\Hub\Responses\Cache\Archive;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Tasks\Download\Download;
use Valvoid\Fusion\Tests\Tasks\Download\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Download\Mocks\DirectoryMock;
use Valvoid\Fusion\Tests\Tasks\Download\Mocks\DirMock;
use Valvoid\Fusion\Tests\Tasks\Download\Mocks\ExtensionMock;
use Valvoid\Fusion\Tests\Tasks\Download\Mocks\ExternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Download\Mocks\FileMock;
use Valvoid\Fusion\Tests\Tasks\Download\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Download\Mocks\HubMock;
use Valvoid\Fusion\Tests\Tasks\Download\Mocks\LogMock;
use Valvoid\Fusion\Tests\Tasks\Download\Mocks\PharDataMock;
use Valvoid\Fusion\Tests\Tasks\Download\Mocks\ZipArchiveMock;
use Valvoid\Fusion\Tests\Test;
use Valvoid\Fusion\Wrappers\Extension;

class DownloadTest extends Test
{
    protected string|array $coverage = [
        Download::class,

        // ballast
        Extension::class
    ];
    private BoxMock $box;
    private GroupMock $group;
    private HubMock $hub;
    private DirectoryMock $directory;
    private DirMock $dir;
    private FileMock $file;
    private ExtensionMock $extension;
    private ZipArchiveMock $zip;
    private Download $task;

    public function __construct()
    {
        $this->box = new BoxMock;
        $this->hub = new HubMock;
        $this->directory = new DirectoryMock;
        $this->dir = new DirMock;
        $this->file = new FileMock;
        $this->group = new GroupMock;
        $this->extension = new ExtensionMock;
        $this->zip = new ZipArchiveMock;
        $this->box->zip = $this->zip;
        $this->task = new Download(
            $this->box,
            $this->group,
            new LogMock,
            $this->hub,
            $this->directory,
            $this->extension,
            $this->file,
            $this->dir,

            // task id for directory
            ["id" => "test"]);

        // cached individual packages
        $this->directory->cache = function () {
            return "/p";
        };

        // individual task cache prefix
        $this->directory->task = function () {
            return "/t";
        };

        $this->testZipArchive();
        $this->testPharData();

        $this->box::unsetInstance();
    }

    public function testZipArchive(): void
    {
        try {
            $this->group->hasDownloadable = true;
            $this->group->externalMetas["i0"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE, [
                    "id" => "i0",
                    "source" => [0]
                ], ["object" => [
                    // bot metadata
                    "version" => "3.4.5"
                ]]);

            $this->group->externalMetas["i1"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE, [
                    "id" => "i1",
                    "source" => [1]
                ]);

            $add =
            $loaded =
            $open =
            $extract =
            $create =
            $exists =
            $put =
            $filenames =
            $rename = [];

            $this->hub->add = function (array $source) use (&$add) {
                $add[] = $source;

                return $source[0];
            };

            $this->hub->execute = function (Closure $callback) {
                $callback(new Archive(0, "/d/0"));
                $callback(new Archive(1, "/d/1"));
            };

            $this->extension->loaded = function (string $extension) use (&$loaded) {
                $loaded[] = $extension;
                return true;
            };

            $this->zip->open = function ($filename) use (&$open) {
                $open[] = $filename;
                return true;
            };

            $this->zip->extract = function (string $to) use (&$extract) {
                $extract[] = $to;
                return true;
            };

            $this->directory->create = function (string $dir) use (&$create) {
                $create[] = $dir;
                return true;
            };

            $this->file->exists = function (string $filename) use (&$exists) {
                $exists[] = $filename;
                return $filename != "/t/test/i0/fusion.json";
            };

            // first level dirs
            // roots like version or branch name
            $this->dir->filenames = function (string $dir) use (&$filenames) {
                $filenames[] = $dir;

                if ($dir == "/t/test/i0")
                    return ["f0", "f1"];

                return [];
            };

            $this->directory->rename = function (string $from, string $to) use (&$rename) {
                $rename[] = [
                    "from" => $from,
                    "to" => $to
                ];
            };

            $this->file->put = function (string $file, mixed $data) use (&$put) {
                $put[] = [
                    "file" => $file,
                    "data" => $data
                ];

                  return 1;
            };

            $this->task->execute();

            if ($create != ["/p/i0", "/p/i1"] ||
                $filenames != ["/t/test/i0"] ||
                $open != ["/d/0/archive.zip", "/d/1/archive.zip"] ||
                $extract != ["/t/test/i0", "/t/test/i1"] ||
                $add != [[0], [1]] ||
                $loaded != ["zip"] ||
                $exists != ["/t/test/i0/fusion.json",
                    "/t/test/i0/f0/fusion.json", "/t/test/i1/fusion.json"] ||
                $rename != [[
                    "from" => "/t/test/i0/f0",
                    "to" => "/p/i0"
                ],[
                    "from" => "/t/test/i1",
                    "to" => "/p/i1"]] ||
                $put != [[
                    "file" => "/p/i0/fusion.bot.php",
                    "data" => "<?php\n" .
                        "// Auto-generated by Fusion package manager.\n" .
                        "// Do not modify.\n" .
                        "return [\n" .
                        "\t\"version\" => \"3.4.5\"\n" .
                        "];"
                ]])
                $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }

    public function testPharData(): void
    {
        try {
            $this->group->hasDownloadable = true;
            $this->group->externalMetas = ["i0" => new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE, [
                "id" => "i0",
                "source" => [0]
            ], ["object" => [
                // bot metadata
                "version" => "3.4.5"
            ]])];

            $add =
            $loaded =
            $mock =
            $extract =
            $create =
            $exists =
            $put =
            $filenames =
            $rename = [];

            $this->hub->add = function (array $source) use (&$add) {
                $add[] = $source;

                return $source[0];
            };

            $this->hub->execute = function (Closure $callback) {
                $callback(new Archive(0, "/d/0"));
            };

            $this->box->phar = function (string $filename) use (&$mock, &$extract) {
                $m = new PharDataMock($filename);

                $m->extract = function ($directory, $files, $overwrite) use (&$extract) {
                    $extract[] = [
                        "directory" => $directory,
                        "files" => $files,
                        "overwrite" => $overwrite
                    ];

                    return true;
                };

                $mock[] = $m;

                return $m;
            };

            $this->extension->loaded = function (string $extension) use (&$loaded) {
                $loaded[] = $extension;

                // no zip extension
                return false;
            };

            $this->zip->open = function ($filename) use (&$open) {
                $open[] = $filename;

                // no zip extension
                return false;
            };

            $this->zip->extract = function (string $to) use (&$extract) {
                $extract[] = $to;
                return true;
            };

            $this->directory->create = function (string $dir) use (&$create) {
                $create[] = $dir;
                return true;
            };

            $this->file->exists = function (string $filename) use (&$exists) {
                $exists[] = $filename;
                return $filename != "/t/test/i0/fusion.json";
            };

            // first level dirs
            // roots like version or branch name
            $this->dir->filenames = function (string $dir) use (&$filenames) {
                $filenames[] = $dir;

                if ($dir == "/t/test/i0")
                    return ["f0", "f1"];

                return [];
            };

            $this->directory->rename = function (string $from, string $to) use (&$rename) {
                $rename[] = [
                    "from" => $from,
                    "to" => $to
                ];
            };

            $this->file->put = function (string $file, mixed $data) use (&$put) {
                $put[] = [
                    "file" => $file,
                    "data" => $data
                ];

                return 1;
            };

            $this->task->execute();

            if (sizeof($mock) != 1 ||
                !($mock[0] instanceof PharDataMock) ||
                $mock[0]->filename != "/d/0/archive.zip" ||
                $create != ["/p/i0"] ||
                $filenames != ["/t/test/i0"] ||
                $extract != [[
                    "directory" => "/t/test/i0",
                    "files" => null,
                    "overwrite" => true]] ||
                $add != [[0]] ||
                $loaded != ["zip"] ||
                $exists != ["/t/test/i0/fusion.json", "/t/test/i0/f0/fusion.json"] ||
                $rename != [[
                    "from" => "/t/test/i0/f0",
                    "to" => "/p/i0"]] ||
                $put != [[
                    "file" => "/p/i0/fusion.bot.php",
                    "data" => "<?php\n" .
                        "// Auto-generated by Fusion package manager.\n" .
                        "// Do not modify.\n" .
                        "return [\n" .
                        "\t\"version\" => \"3.4.5\"\n" .
                        "];"
                ]])
                $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }
}