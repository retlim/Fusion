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

use Valvoid\Fusion\Log\Events\Errors\Request;
use Valvoid\Reflex\Test\Wrapper;

class RequestTest extends Wrapper
{
    public function testMapping(): void
    {
        $path = [
            "layer" => "#3",
            "breadcrumb" => ["#4", "#5"],
            "source" => "#6"
        ];

        $event = new Request(11, "#0",
            ["#1", "#2"]);

        $event->setPath([$path]);
        $this->validate($event->getId())
            ->as(11);

        $this->validate($event->getMessage())
            ->as("#0");

        $this->validate($event->getSources())
            ->as(["#1", "#2"]);

        $this->validate($event->getPath())
            ->as([$path]);

        $this->validate("$event")
            ->as("\nin: #3" .
                "\nat: #4 | #5" .
                "\nas: #6" .
                "\nby: #1" .
                "\nby: #2" .
                "\nis: #0");
    }
}