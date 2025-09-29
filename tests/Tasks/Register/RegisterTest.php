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
 */

namespace Valvoid\Fusion\Tests\Tasks\Register;

use Exception;
use Valvoid\Fusion\Tasks\Register\Register;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Tests\Tasks\Register\Mocks\BoxMock;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;
use Valvoid\Fusion\Tests\Tasks\Register\Mocks\DirectoryMock;
use Valvoid\Fusion\Tests\Tasks\Register\Mocks\ExternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Register\Mocks\FileMock;
use Valvoid\Fusion\Tests\Tasks\Register\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Register\Mocks\InternalMetadataMock;
use Valvoid\Fusion\Tests\Tasks\Register\Mocks\LogMock;
use Valvoid\Fusion\Tests\Test;

/**
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class RegisterTest extends Test
{
    protected string|array $coverage = Register::class;
    private BoxMock $box;

    public function __construct()
    {
        $this->box = new BoxMock;

        $this->testRefreshAutoloader();
        $this->testNewStateAutoloader();

        $this->box::unsetInstance();
    }

    public function testRefreshAutoloader(): void
    {
        try {
            $group = new GroupMock;
            $directory = new DirectoryMock;
            $file = new FileMock;
            $register = new Register(
                box: $this->box,
                group: $group,
                directory: $directory,
                log: new LogMock,
                file: $file,
                config: []
            );

            $group->hasDownloadable = false;
            $group->internalMetas["i0"] = new InternalMetadataMock(
                InternalCategory::RECYCLABLE, [
                "source" => "/s0",
                "dir" => "",
                "structure" => [
                    "cache" => "/c0",
                ]
            ]);

            $group->internalRoot = $group->internalMetas["i0"];
            $group->internalMetas["i1"] = new InternalMetadataMock(
                InternalCategory::RECYCLABLE, [
                "source" => "/s1",
                "dir" => "/deps/i1",
                "structure" => [
                    "cache" => "/c1"
                ]
            ]);

            $group->internalMetas["i2"] = new InternalMetadataMock(
                InternalCategory::RECYCLABLE, [
                "source" => "/s2",
                "dir" => "/deps/i2",
                "structure" => [
                    "cache" => "/c2"
                ]
            ]);

            $group->internalMetas["i3"] = new InternalMetadataMock(
                InternalCategory::OBSOLETE, []);

            $get =
            $create =
            $put =
            $exists =
            $require = [];
            $directory->cache = function () {return "/#";};
            $directory->create = function (string $dir) use (&$create) {
                $create[] = $dir;
            };

            $file->get = function (string $file) use (&$get) {
                $get[] = $file;
                return "ASAP = [];LAZY = []";
            };

            $file->put = function (string $file, mixed $data) use (&$put) {
                $put[] = [
                    "file" => $file,
                    "data" => $data
                ];
                return 1;
            };

            $file->exists = function (string $file) use (&$exists) {
                $exists[] = $file;
                return true;
            };

            $file->require = function (string $file) use (&$require) {
                $require[] = $file;
                if ($file == "/s0/c0/loadable/lazy.php")
                    return ["I0" => "/d0/f0.php"];

                if ($file == "/s0/c0/loadable/asap.php")
                    return ["/f1.php"];

                if ($file == "/s1/c1/loadable/lazy.php")
                    return ["I1" => "/d1/f2.php"];

                if ($file == "/s1/c1/loadable/asap.php")
                    return ["/f3.php"];

                if ($file == "/s2/c2/loadable/lazy.php")
                    return ["I2" => "/d2/f4.php"];

                if ($file == "/s2/c2/loadable/asap.php")
                    return ["/f5.php"];

                return [];
            };

            $register->execute();

            if ($require != [
                    "/s0/c0/loadable/lazy.php",
                    "/s0/c0/loadable/asap.php",
                    "/s1/c1/loadable/lazy.php",
                    "/s1/c1/loadable/asap.php",
                    "/s2/c2/loadable/lazy.php",
                    "/s2/c2/loadable/asap.php"] ||
                $exists != [
                    "/s0/c0/loadable/lazy.php",
                    "/s0/c0/loadable/asap.php",
                    "/s1/c1/loadable/lazy.php",
                    "/s1/c1/loadable/asap.php",
                    "/s2/c2/loadable/lazy.php",
                    "/s2/c2/loadable/asap.php"] ||
                $create != ["/#"] ||
                $get != [dirname(__DIR__, 3) .
                    "/src/Tasks/Register/Autoloader.php"] ||
                $put != [[
                    "file" => "/#/Autoloader.php",
                    "data" => "ASAP = [" .
                        "\n\t\t'/f1.php'," .
		                "\n\t\t'/deps/i1/f3.php'," .
		                "\n\t\t'/deps/i2/f5.php'," .
                    "\n\t];LAZY = [" .
                        "\n\t\t'I0' => '/d0/f0.php'," .
                        "\n\t\t'I1' => '/deps/i1/d1/f2.php'," .
                        "\n\t\t'I2' => '/deps/i2/d2/f4.php'," .
	                "\n\t]"
                ]])
                $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }

    public function testNewStateAutoloader(): void
    {
        try {
            $group = new GroupMock;
            $directory = new DirectoryMock;
            $file = new FileMock;
            $register = new Register(
                box: $this->box,
                group: $group,
                directory: $directory,
                log: new LogMock,
                file: $file,
                config: []
            );

            $group->hasDownloadable = true;
            $group->internalMetas = ["i0" => new InternalMetadataMock(
                InternalCategory::OBSOLETE, [
                "id" => "i0",
                "dir" => "",
                "structure" => [
                    "cache" => "/c0"
                ]
            ])];
            $group->internalRoot = $group->internalMetas["i0"];
            $group->externalMetas["i0"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE,[
                "dir" => "",
                "structure" => [
                    "cache" => "/c0",
                ]
            ]);

            $group->externalMetas["i1"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE,[
                "dir" => "/deps/i1",
                "structure" => [
                    "cache" => "/c1"
                ]
            ]);

            $group->externalMetas["i2"] = new ExternalMetadataMock(
                ExternalCategory::DOWNLOADABLE,[
                "dir" => "/deps/i2",
                "structure" => [
                    "cache" => "/c2",
                ]
            ]);

            $get =
            $create =
            $put =
            $exists =
            $require = [];
            $directory->packages = function () {return "/#";};
            $directory->create = function (string $dir) use (&$create) {
                $create[] = $dir;
            };

            $file->get = function (string $file) use (&$get) {
                $get[] = $file;

                return "ASAP = [];LAZY = []";
            };

            $file->put = function (string $file, mixed $data) use (&$put) {
                $put[] = [
                    "file" => $file,
                    "data" => $data
                ];

                return 1;
            };

            $file->exists = function (string $file) use (&$exists) {
                $exists[] = $file;
                return true;
            };

            $file->require = function (string $file) use (&$require) {
                $require[] = $file;

                if ($file == "/#/i0/c0/loadable/lazy.php")
                    return ["I0" => "/d0/f0.php"];

                if ($file == "/#/i0/c0/loadable/asap.php")
                    return ["/f1.php"];

                if ($file == "/#/i1/c1/loadable/lazy.php")
                    return ["I1" => "/d1/f2.php"];

                if ($file == "/#/i1/c1/loadable/asap.php")
                    return ["/f3.php"];

                if ($file == "/#/i2/c2/loadable/lazy.php")
                    return ["I2" => "/d2/f4.php"];

                if ($file == "/#/i2/c2/loadable/asap.php")
                    return ["/f5.php"];

                return [];
            };

            $register->execute();

            if ($require != [
                    "/#/i0/c0/loadable/lazy.php",
                    "/#/i0/c0/loadable/asap.php",
                    "/#/i1/c1/loadable/lazy.php",
                    "/#/i1/c1/loadable/asap.php",
                    "/#/i2/c2/loadable/lazy.php",
                    "/#/i2/c2/loadable/asap.php"] ||
                $exists != [
                    "/#/i0/c0/loadable/lazy.php",
                    "/#/i0/c0/loadable/asap.php",
                    "/#/i1/c1/loadable/lazy.php",
                    "/#/i1/c1/loadable/asap.php",
                    "/#/i2/c2/loadable/lazy.php",
                    "/#/i2/c2/loadable/asap.php"] ||
                $create != ["/#/i0/c0"] ||
                $get != [dirname(__DIR__, 3) .
                    "/src/Tasks/Register/Autoloader.php"] ||
                $put != [[
                    "file" => "/#/i0/c0/Autoloader.php",
                    "data" => "ASAP = [" .
                        "\n\t\t'/f1.php'," .
                        "\n\t\t'/deps/i1/f3.php'," .
                        "\n\t\t'/deps/i2/f5.php'," .
                        "\n\t];LAZY = [" .
                        "\n\t\t'I0' => '/d0/f0.php'," .
                        "\n\t\t'I1' => '/deps/i1/d1/f2.php'," .
                        "\n\t\t'I2' => '/deps/i2/d2/f4.php'," .
                        "\n\t]"
                ]])
                $this->handleFailedTest();

        } catch (Exception) {
            $this->handleFailedTest();
        }
    }
}