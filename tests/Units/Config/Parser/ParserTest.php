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

namespace Valvoid\Fusion\Tests\Units\Config\Parser;

use Valvoid\Box\Box;
use Valvoid\Fusion\Config\Parser\Parser;
use Valvoid\Fusion\Config\Parser\Hub;
use Valvoid\Fusion\Config\Parser\Log;
use Valvoid\Fusion\Config\Parser\Tasks;
use Valvoid\Reflex\Test\Wrapper;

class ParserTest extends Wrapper
{
    public function testParse(): void
    {
        $box = $this->createMock(Box::class);
        $hub = $this->createMock(Hub::class);
        $log = $this->createMock(Log::class);
        $tasks = $this->createMock(Tasks::class);
        $parser = new Parser($box);
        $config = [
            "tasks" => [],
            "hub" => [],
            "log" => []
        ];

        $box->fake("get")
            ->expect(class: Tasks::class)
            ->return($tasks)
            ->expect(class: Hub::class)
            ->return($hub)
            ->expect(class: Log::class)
            ->return($log);

        $tasks->fake("parse")
            ->set(config: 2);

        $log->fake("parse")
            ->set(config: 1);

        $hub->fake("parse")
            ->set(config: 0);

        $parser->parse($config);
        $this->validate($config)
            ->as([
                "tasks" => 2,
                "hub" => 0,
                "log" => 1
            ]);
    }
}