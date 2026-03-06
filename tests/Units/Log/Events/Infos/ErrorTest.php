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

namespace Valvoid\Fusion\Tests\Units\Log\Events\Infos;

use Valvoid\Fusion\Log\Events\Infos\Error;
use Valvoid\Reflex\Test\Wrapper;

class ErrorTest extends Wrapper
{
    public function testMapping(): void
    {
        $path = [
            "line" => "#1",
            "file" => "#2",
            "type" => "#3",
            "class" => "#4",
            "function" => "#5"
        ];

        $event = new Error("#0", 11, [$path]);

        $this->validate($event->getMessage())
            ->as("#0");

        $this->validate($event->getCode())
            ->as(11);

        $this->validate($event->getPath())
            ->as([$path]);

        $this->validate("$event")
            ->as("\nin: #1 - #2" .
                "\nat: #4#3#5()" .
                "\nis: #0 | code: 11"
            );
    }
}