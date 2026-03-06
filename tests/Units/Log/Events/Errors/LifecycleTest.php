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

use Valvoid\Fusion\Log\Events\Errors\Lifecycle;
use Valvoid\Reflex\Test\Wrapper;

class LifecycleTest extends Wrapper
{
    public function testMapping(): void
    {
        $path = [
            "layer" => "#4",
            "breadcrumb" => ["#5", "#6"],
            "source" => "#7"];

        $event = new Lifecycle("#0", "#1",
            ["#2", "#3"], [$path]);

        $this->validate($event->getMessage())
            ->as("#0");

        $this->validate($event->getPath())
            ->as([$path]);

        $this->validate($event->getLayer())
            ->as("#1");

        $this->validate($event->getBreadcrumb())
            ->as(["#2", "#3"]);

        $this->validate("$event")
            ->as("\nin: #4" .
                "\nat: #5 | #6" .
                "\nas: #7" .
                "\nin: #1" .
                "\nat: #2 | #3" .
                "\nis: #0");
    }
}