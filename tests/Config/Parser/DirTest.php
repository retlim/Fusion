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

namespace Valvoid\Fusion\Tests\Config\Parser;

use Valvoid\Fusion\Config\Parser\Dir;
use Valvoid\Fusion\Tests\Test;

/**
 * Config dir parser test.
 *
 * @Copyright Valvoid
 * @license GNU GPLv3
 */
class DirTest extends Test
{
    protected string|array $coverage = Dir::class;

    public function __construct()
    {
        $this->testRootDir();
    }

    public function testRootDir(): void
    {
        $dir = Dir::getNonNestedPath(__DIR__);
        $assertion = dirname(__DIR__, 3);

        if ($dir !== $assertion) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}