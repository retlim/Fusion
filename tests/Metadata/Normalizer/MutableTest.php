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

use Valvoid\Fusion\Bus\Events\Metadata as MetadataEvent;
use Valvoid\Fusion\Metadata\Normalizer\Mutable;
use Valvoid\Fusion\Tests\Metadata\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Metadata\Mocks\BusMock;
use Valvoid\Fusion\Tests\Test;

class MutableTest extends Test
{
    protected string|array $coverage = Mutable::class;
    private BoxMock $box;
    private BusMock $bus;
    public function __construct()
    {
        $this->box = new BoxMock;
        $this->bus = new BusMock;
        $this->box->get = function (string $class, ...$args) {
            if ($class == "Valvoid\Fusion\Bus\Events\Metadata")
                return new MetadataEvent(...$args);
        };

        $this->testPaths();
        $this->box->unsetInstance();
    }

    public function testPaths(): void
    {
        $paths = [];

        (new Mutable($this->box, $this->bus))
            ->normalize(["path1", "path2"], $paths);

        if ($paths !== ["path1", "path2"])
            $this->handleFailedTest();
    }
}