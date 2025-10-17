<?php
/*
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

namespace Valvoid\Fusion\Tests\Util\Reference;

use Valvoid\Fusion\Tests\Test;
use Valvoid\Fusion\Util\Reference\Normalizer;

class NormalizerTest extends Test
{
    protected string|array $coverage = Normalizer::class;

    private array $versions = [
        ["major" => 3, "minor" => 0, "patch" => 0, "release" => "", "build" => ""],
        ["major" => 2, "minor" => 0, "patch" => 2, "release" => "", "build" => ""],
        ["major" => 2, "minor" => 0, "patch" => 1, "release" => "", "build" => ""],
        ["major" => 2, "minor" => 0, "patch" => 0, "release" => "", "build" => ""],
        ["major" => 1, "minor" => 0, "patch" => 3, "release" => "", "build" => ""],
        ["major" => 1, "minor" => 0, "patch" => 2, "release" => "", "build" => ""],
        ["major" => 1, "minor" => 0, "patch" => 1, "release" => "", "build" => ""],
        ["major" => 1, "minor" => 0, "patch" => 0, "release" => "", "build" => ""],
        ["major" => 0, "minor" => 2, "patch" => 0, "release" => "", "build" => "1"],
        ["major" => 0, "minor" => 2, "patch" => 0, "release" => "b", "build" => ""],
        ["major" => 0, "minor" => 1, "patch" => 0, "release" => "", "build" => ""]
    ];

    public function __construct()
    {
        $this->testAbsoluteReference();
        $this->testDefaultRangeReference();
        $this->testReleaseReference();
        $this->testAndReference();
        $this->testOrReference();
        $this->testBracketReference();
    }

    public function testBracketReference(): void
    {
        $versions = Normalizer::getFilteredVersions(
            $this->versions, [[
                "major" => 10,
                "minor" => 3,
                "patch" => 0,
                "build" => "",
                "release" => "",
                "sign" => ""

            // OR brackets
            // after no match
            ], "||", [[
                "major" => 2,
                "minor" => 0,
                "patch" => 2,
                "build" => "",
                "release" => "",

                // min over major
                // greater than or equal
                "sign" => ">="

            // unreachable
            ], "||", [
                "major" => 0,
                "minor" => 1,
                "patch" => 0,
                "build" => "",
                "release" => "",
                "sign" => "=="
            ]]]
        );

        if (array_values($versions) !== [[
                "major" => 3,
                "minor" => 0,
                "patch" => 0,
                "release" => "",
                "build" => ""
            ], [
                "major" => 2,
                "minor" => 0,
                "patch" => 2,
                "release" => "",
                "build" => ""
            ]]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testOrReference(): void
    {
        $versions = Normalizer::getFilteredVersions(
            $this->versions, [[
                "major" => 0,
                "minor" => 3,
                "patch" => 0,
                "build" => "",
                "release" => "",

                // no sign
                // default range (>=0.0.0 && <1.0.0)
                // all bigger non-breaking changes
                "sign" => ""

            // OR
            ], "||", [
                "major" => 3,
                "minor" => 0,
                "patch" => 0,
                "build" => "",
                "release" => "",

                // greater than or equal
                "sign" => ">="
            ]]
        );

        if (array_values($versions) !== [[
                "major" => 3,
                "minor" => 0,
                "patch" => 0,
                "release" => "",
                "build" => ""
            ]]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testAndReference(): void
    {
        $versions = Normalizer::getFilteredVersions(
            $this->versions, [[
                "major" => 0,
                "minor" => 1,
                "patch" => 0,
                "build" => "",

                // b > a and
                // empty production > a
                "release" => "a",

                // no sign
                // default range (>=0.0.0 && <1.0.0)
                // all bigger non-breaking changes
                "sign" => ""

            // AND
            ], "&&", [
                "major" => 0,
                "minor" => 2,
                "patch" => 0,
                "build" => "",
                "release" => "",

                // greater than or equal
                "sign" => ">="
            ]]
        );

        if (array_values($versions) !== [[
                "major" => 0,
                "minor" => 2,
                "patch" => 0,
                "release" => "",
                "build" => "1"
            ]]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testReleaseReference(): void
    {
        $versions = Normalizer::getFilteredVersions(
            $this->versions, [[
                "major" => 0,
                "minor" => 1,
                "patch" => 0,
                "build" => "",

                // b > a and
                // empty production > a
                "release" => "a",

                // no sign
                // default range (>=0.0.0 && <1.0.0)
                // all bigger non-breaking changes
                "sign" => ""
            ]]
        );

        if (array_values($versions) !== [[
                "major" => 0,
                "minor" => 2,
                "patch" => 0,
                "release" => "",
                "build" => "1"
            ], [
                "major" => 0,
                "minor" => 2,
                "patch" => 0,
                "release" => "b",
                "build" => ""
            ], [
                "major" => 0,
                "minor" => 1,
                "patch" => 0,
                "release" => "",
                "build" => ""
            ]]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testDefaultRangeReference(): void
    {
        $versions = Normalizer::getFilteredVersions(
            $this->versions, [[
                "major" => 0,
                "minor" => 1,
                "patch" => 0,
                "build" => "",

                // empty
                // production only
                "release" => "",

                // no sign
                // default range (>=0.0.0 && <1.0.0)
                // all bigger non-breaking changes
                "sign" => ""
            ]]
        );

        if (array_values($versions) !== [[
                "major" => 0,
                "minor" => 2,
                "patch" => 0,
                "release" => "",
                "build" => "1"
            ], [
                "major" => 0,
                "minor" => 1,
                "patch" => 0,
                "release" => "",
                "build" => ""
            ]]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testAbsoluteReference(): void
    {
        $versions = Normalizer::getFilteredVersions(
            $this->versions, [[
                "major" => 1,
                "minor" => 0,
                "patch" => 0,
                "release" => "",
                "build" => "",

                // absolute
                "sign" => "=="
            ]]
        );

        if (array_values($versions) !== [[
                "major" => 1,
                "minor" => 0,
                "patch" => 0,
                "release" => "",
                "build" => ""
            ]]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}