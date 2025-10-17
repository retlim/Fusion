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

namespace Valvoid\Fusion\Tests\Util\Version;

use Valvoid\Fusion\Tests\Test;
use Valvoid\Fusion\Util\Version\Interpreter;

class InterpreterTest extends Test
{
    protected string|array $coverage = Interpreter::class;

    public function __construct()
    {
        $this->testCoreIsBiggerThan();
        $this->testReleaseIsBiggerThan();
        $this->testBuildIsBiggerThan();
    }

    public function testCoreIsBiggerThan(): void
    {
        $comparison = Interpreter::isBiggerThan([
            "build" => "",
            "release" => "",
            "major" => "1",
            "minor" => "0",
            "patch" => "1" // fix is bigger
        ], [
            "build" => "",
            "release" => "",
            "major" => "1",
            "minor" => "0",
            "patch" => "0"
        ]);

        // assert true
        if ($comparison !== true) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testReleaseIsBiggerThan(): void
    {
        $comparison = Interpreter::isBiggerThan([
            "build" => "",
            "release" => "", // production is bigger
            "major" => "1",
            "minor" => "0",
            "patch" => "0"
        ], [
            "build" => "",
            "release" => "beta",
            "major" => "1",
            "minor" => "0",
            "patch" => "0"
        ]);

        // assert true
        if ($comparison !== true) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testBuildIsBiggerThan(): void
    {
        $comparison = Interpreter::isBiggerThan([
            "build" => "a", // progressive is bigger
            "release" => "",
            "major" => "1",
            "minor" => "0",
            "patch" => "0"
        ], [
            "build" => "",
            "release" => "",
            "major" => "1",
            "minor" => "0",
            "patch" => "0"
        ]);

        // assert true
        if ($comparison !== true) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}