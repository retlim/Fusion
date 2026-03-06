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

use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Reflex\Test\Wrapper;

class ContentTest extends Wrapper
{
    public function testInternalMapping(): void
    {
        $event = new Content([
            "id" => "#0",
            "version" => "#1",
            "name" => "#2",
            "description" => "#3",
            "dir" => "#4",
            "source" => "#5"
        ]);

        $this->validate($event->getId())
            ->as("#0");

        $this->validate($event->getVersion())
            ->as("#1");

        $this->validate($event->getName())
            ->as("#2");

        $this->validate($event->getType())
            ->as("internal");

        $this->validate($event->getDescription())
            ->as("#3");

        $this->validate($event->getDir())
            ->as("#4");

        $this->validate($event->getSource())
            ->as("#5");

        $this->validate("$event")
            ->as("id: #0" .
                "\nversion: #1" .
                "\nname: #2" .
                "\ndescription: #3" .
                "\ntype: internal" .
                "\nsource: #5" .
                "\ndir: #4"
            );
    }

    public function testExternalMapping(): void
    {
        $event = new Content([
            "id" => "#0",
            "version" => "#1",
            "name" => "#2",
            "description" => "#3",
            "dir" => "#4",
            "source" => [
                "api" => "#5",
                "path" => "#6",
                "prefix" => "#7",
                "reference" => "#8"
            ]
        ]);

        $this->validate($event->getId())
            ->as("#0");

        $this->validate($event->getVersion())
            ->as("#1");

        $this->validate($event->getName())
            ->as("#2");

        $this->validate($event->getType())
            ->as("external");

        $this->validate($event->getDescription())
            ->as("#3");

        $this->validate($event->getDir())
            ->as("#4");

        $this->validate($event->getSource())
            ->as("#5#6/#7#8");

        $this->validate("$event")
            ->as("id: #0" .
                "\nversion: #1" .
                "\nname: #2" .
                "\ndescription: #3" .
                "\ntype: external" .
                "\nsource: #5#6/#7#8" .
                "\ndir: #4"
            );
    }
}