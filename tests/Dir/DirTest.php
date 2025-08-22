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

namespace Valvoid\Fusion\Tests\Dir;

use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Tests\Dir\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Hub test.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class DirTest extends Test
{
    protected string|array $coverage = Dir::class;

    private BoxMock $box;

    public function __construct()
    {
        $this->box = new BoxMock;

        // static
        $this->testStaticInterface();
        $this->box::unsetInstance();

    }

    public function testStaticInterface(): void
    {
        Dir::getTaskDir();
        Dir::getStateDir();
        Dir::getCacheDir();
        Dir::getOtherDir();
        Dir::getPackagesDir();
        Dir::getRootDir();
        Dir::createDir("",1);
        Dir::rename("","");
        Dir::copy("","");
        Dir::delete("");
        Dir::clear("", "");

        // static functions connected to same non-static functions
        if ($this->box->dir->calls !== [
                "getTaskDir",
                "getStateDir",
                "getCacheDir",
                "getOtherDir",
                "getPackagesDir",
                "getRootDir",
                "createDir",
                "rename",
                "copy",
                "delete",
                "clear"]) {

            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}