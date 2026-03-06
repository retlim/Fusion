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

namespace Valvoid\Fusion\Tests\Units\Log\Events\Errors;

use Valvoid\Fusion\Log\Events\Errors\Environment;
use Valvoid\Reflex\Test\Wrapper;

class EnvironmentTest extends Wrapper
{
    public function testMapping(): void
    {
        $path = [
            "layer" => "#1",
            "breadcrumb" => ["#2", "#3"],
            "source" => "#4"
        ];

        $event = new Environment("#0", [$path], "#5", ["#6", "#7"]);

        $this->validate($event->getMessage())
            ->as("#0");

        $this->validate($event->getPath())
            ->as([$path]);

        $this->validate($event->getLayer())
            ->as("#5");

        $this->validate($event->getBreadcrumb())
            ->as(["#6", "#7"]);

        $this->validate("$event")
            ->as("\nin: #1" .
                "\nat: #2 | #3" .
                "\nas: #4" .
                "\nin: #5" .
                "\nat: #6 | #7" .
                "\nis: #0");
    }
}