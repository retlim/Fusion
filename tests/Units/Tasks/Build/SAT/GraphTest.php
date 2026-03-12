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

namespace Valvoid\Fusion\Tests\Units\Tasks\Build\SAT;

use Valvoid\Fusion\Tasks\Build\SAT\Clause\State;
use Valvoid\Fusion\Tasks\Build\SAT\Graph;
use Valvoid\Reflex\Test\Wrapper;

class GraphTest extends Wrapper
{
    public function testFallback(): void
    {
        // all placeholder true state
        // must not be valid
        // only result reflection must be true
        $graph = new Graph;

        $graph->addRootNode(1, true, 1);
        $graph->addRootNode(2, true, 2);
        $graph->addLeafNode([2], 3, true, 2);
        $graph->addLeafNode([2], 4, true, 2);
        $graph->addLeafNode([3, 4], 5, true, 2);
        $graph->addLeafNode([5], 6, true, 2);
        $graph->addLeafNode([5], 7, true, 2);
        $graph->addLeafNode([5], 8, true, 2);

        // different states
        // result in conflict
        $graph->addLeafNode([5, 1], 9, true, 2);
        $graph->addLeafNode([1, 6], 9, false, 2);

        $fallback = $graph->getConflictFallback();
        $clause = $fallback["clause"];
        $literals = $clause->getLiterals();

        $this->validate($fallback["level"])
            ->as(1);

        $this->validate($clause->getState())
            ->as(State::UNIT);

        $this->validate(sizeof($literals))
            ->as(2);

        $this->validate(isset($literals[1]))
            ->as(true);

        $this->validate(isset($literals[5]))
            ->as(true);
    }

    public function testConflictSeparation(): void
    {
        // conflict is extra node
        $graph = new Graph;

        $graph->addRootNode(0, true, 0);
        $graph->addLeafNode([0], 2, false, 1);
        $graph->addLeafNode([0], 2, true, 1);
        $this->validate($graph->getNodes())
            ->as([
                0 => [
                    "roots" => [],
                    "level" => 0,
                    "state" => true
                ]
            ]);
    }

    public function testNodeMutation(): void
    {
        $graph = new Graph;

        $graph->addRootNode(0, true, 0);
        $graph->addRootNode(1, true, 1);
        $graph->addLeafNode([0, 1], 2, true, 1);
        $graph->addRootNode(0, false, 2);
        $this->validate($graph->getNodes())
            ->as([
                0 => [
                    "roots" => [],
                    "level" => 2,
                    "state" => false
                ],
                1 => [
                    "roots" => [],
                    "level" => 1,
                    "state" => true
                ],
                2 => [
                    "roots" => [0, 1],
                    "level" => 1,
                    "state" => true
                ]
            ]);
    }
}