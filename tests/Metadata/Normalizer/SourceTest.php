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

namespace Valvoid\Fusion\Tests\Metadata\Normalizer;

use Valvoid\Fusion\Metadata\Normalizer\Source;
use Valvoid\Fusion\Tests\Test;

class SourceTest extends Test
{
    protected string|array $coverage = Source::class;

    public function __construct()
    {
        $this->testSources();
    }

    public function testSources(): void
    {
        $sources = [];

        // leading slash legacy ballast
        Source::normalize([
            ["/path1" => "/source1"],
            ["/path2" => "/source2"],
            ["/path2" => "/source3"]
        ],
            $sources);

        if ($sources !== [
                "/path1" => ["source1"],
                "/path2" => [
                    "source2",
                    "source3"
                ]]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}