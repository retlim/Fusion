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

use Valvoid\Fusion\Log\Events\Errors\Metadata;
use Valvoid\Reflex\Test\Wrapper;

class MetadataTest extends Wrapper
{
    public function testMapping(): void
    {
        $path = [
            "layer" => "#5",
            "breadcrumb" => ["#6", "#7"],
            "source" => "#8"];

        $event = new Metadata("#0", "#1",
            "#2", ["#3", "#4"], 11);

        $event->setPath([$path]);
        $this->validate($event->getSource())
            ->as("#0");

        $this->validate($event->getMessage())
            ->as("#1");

        $this->validate($event->getLayer())
            ->as("#2");

        $this->validate($event->getBreadcrumb())
            ->as(["#3", "#4"]);

        $this->validate($event->getRow())
            ->as(11);

        $this->validate($event->getPath())
            ->as([$path]);

        $this->validate("$event")
            ->as("\nin: #5" .
                "\nat: #6 | #7" .
                "\nas: #8" .
                "\nin: #2" .
                "\nat: #3 | #4" .
                "\nis: #1");
    }
}