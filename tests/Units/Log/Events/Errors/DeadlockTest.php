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

use Valvoid\Fusion\Log\Events\Errors\Deadlock;
use Valvoid\Reflex\Test\Wrapper;

class DeadlockTest extends Wrapper
{
    public function testMapping(): void
    {
        $lockedPath = [
            "layer" => "#1",
            "breadcrumb" => ["#2", "#3"],
            "source" => "#4",
        ];

        $conflictPath = [
            "layer" => "#5",
            "breadcrumb" => ["#6", "#7"],
            "source" => "#8",
        ];

        $event = new Deadlock("#0",
            [$lockedPath], [$conflictPath],
            "#9", "#10",
            ["#11", "#12"], ["#13", "#14"]);

        $this->validate($event->getMessage())
            ->as("#0");

        $this->validate($event->getLockedPath())
            ->as([$lockedPath]);

        $this->validate($event->getConflictPath())
            ->as([$conflictPath]);

        $this->validate($event->getLockedLayer())
            ->as("#9");

        $this->validate($event->getConflictLayer())
            ->as("#10");

        $this->validate($event->getLockedBreadcrumb())
            ->as(["#11", "#12"]);

        $this->validate($event->getConflictBreadcrumb())
            ->as(["#13", "#14"]);

        $this->validate("$event")
            ->as("\nin: #1" .
                "\nat: #2 | #3" .
                "\nas: #4" .
                "\nin: #9" .
                "\nat: #11 | #12" .
                "\n    ---" .
                "\nin: #5" .
                "\nat: #6 | #7" .
                "\nas: #8" .
                "\nin: #10" .
                "\nat: #13 | #14" .
                "\nis: #0");
    }
}