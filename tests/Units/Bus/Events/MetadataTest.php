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

namespace Valvoid\Fusion\Tests\Units\Bus\Events;

use Valvoid\Fusion\Bus\Events\Metadata;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Reflex\Test\Wrapper;

class MetadataTest extends Wrapper
{
    public function testParameterMapping(): void
    {
        $event = new Metadata(
            "###",
            Level::INFO,
            ["#0"],
            ["#1"]);

        $this->validate($event->getMessage())
            ->as("###");

        $this->validate($event->getLevel())
            ->as(Level::INFO);

        $this->validate($event->getBreadcrumb())
            ->as(["#0"]);

        $this->validate($event->getAbstract())
            ->as(["#1"]);
    }
}