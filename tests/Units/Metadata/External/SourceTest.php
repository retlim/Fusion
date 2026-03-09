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

namespace Valvoid\Fusion\Tests\Units\Metadata\External;

use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Metadata\External\Parser\Source;
use Valvoid\Reflex\Test\Wrapper;

class SourceTest extends Wrapper
{
    public function testParse(): void
    {
        $bus = $this->createStub(Bus::class);
        $parser = new Source(
            bus: $bus,
            source: "api.com/path1/" .

            // package ID exception
            "'path2/" .

            // offset must be absolute ==
            "==v1.0.0:ref || (v1.0.3 && <=v2.3.4)");

        $this->validate($parser->getId())
            ->as("path1");

        $this->validate($parser->getSource())
            ->as([
                "api" => "api.com",
                "path" => "/path1/path2",
                "prefix" => "v",
                "reference" => [[
                    "build" => "",
                    "release" => "",
                    "major" => "1",
                    "minor" => "0",
                    "patch" => "0",
                    "offset" => "ref",
                    "sign" => "=="
                ], "||", [
                    [
                        "build" => "",
                        "release" => "",
                        "major" => "1",
                        "minor" => "0",
                        "patch" => "3",
                        "sign" => ""
                    ], "&&", [
                        "build" => "",
                        "release" => "",
                        "major" => "2",
                        "minor" => "3",
                        "patch" => "4",
                        "sign" => "<="
                    ]
                ]]
            ]);
    }
}