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
use Valvoid\Fusion\Metadata\Normalizer\Stateful;
use Valvoid\Fusion\Tests\Metadata\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Metadata\Mocks\BusMock;
use Valvoid\Fusion\Tests\Test;

class StatefulTest extends Test
{
    protected string|array $coverage = Stateful::class;
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
        $this->testPath();
        $this->box->unsetInstance();
    }

    public function testPath(): void
    {
        $path = "";

        (new Stateful($this->box, $this->bus))
            ->normalize(["path"], $path);

        if ($path !== "path")
            $this->handleFailedTest();
    }
}