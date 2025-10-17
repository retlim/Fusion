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

namespace Valvoid\Fusion\Tests\Metadata\External\Parser;

use Valvoid\Fusion\Metadata\External\Parser\Source;
use Valvoid\Fusion\Tests\Test;

class SourceTest extends Test
{
    /** @var string|array  */
    protected string|array $coverage = Source::class;

    protected string $id;
    protected array $source;

    public function __construct()
    {
        $parser = new Source("api.com/path1/" .

            // package ID exception
            "'path2/" .

            // offset must be absolute ==
            "==v1.0.0:ref || (v1.0.3 && <=v2.3.4)");
        $this->id = $parser->getId();
        $this->source = $parser->getSource();

        $this->testPackageId();
        $this->testApi();
        $this->testPath();
        $this->testPrefix();
        $this->testReference();
    }

    public function testPackageId(): void
    {
        // extracted from path
        if ($this->id !== "path1")
            $this->handleFailedTest();
    }

    public function testApi(): void
    {
        if ($this->source["api"] !== "api.com")
            $this->handleFailedTest();
    }

    public function testPath(): void
    {
        if ($this->source["path"] !== "/path1/path2")
            $this->handleFailedTest();
    }

    public function testPrefix(): void
    {
        if ($this->source["prefix"] !== "v")
            $this->handleFailedTest();
    }

    public function testReference(): void
    {
        if ($this->source["reference"] !== [[
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
            ]])
            $this->handleFailedTest();
    }
}