<?php
/**
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
 */

namespace Valvoid\Fusion\Tests\Config\Parser;

use Throwable;
use Valvoid\Fusion\Config\Parser\Hub;
use Valvoid\Fusion\Config\Parser\Log;
use Valvoid\Fusion\Config\Parser\Parser;
use Valvoid\Fusion\Tests\Config\Parser\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Config\Parser\Mocks\HubParserMock;
use Valvoid\Fusion\Tests\Config\Parser\Mocks\LogParserMock;
use Valvoid\Fusion\Tests\Config\Parser\Mocks\TasksParserMock;
use Valvoid\Fusion\Tests\Test;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class ParserTest extends Test
{
    protected string|array $coverage = Parser::class;
    private BoxMock $box;

    public function __construct()
    {
        $this->box = new BoxMock;

        $this->testReference();

        $this->box::unsetInstance();
    }

    public function testReference(): void
    {
        try {
            $hub =  new HubParserMock;
            $hub->parse = function (&$config) {
                $config = 0;
            };

            $log = new LogParserMock;
            $log->parse = function (&$config) {
                $config = 1;
            };

            $tasks = new TasksParserMock;
            $tasks->parse = function (&$config) {
                $config = 2;
            };

            $this->box->get = function ($class) use ($hub, $log, $tasks) {
                if ($class == Hub::class)
                    return $hub;

                if ($class == Log::class)
                    return $log;

                return $tasks;
            };

            $parser = new Parser($this->box);
            $config = [
                "tasks" => [],
                "hub" => [],
                "log" => []
            ];

            $parser->parse($config);

            if ($config != [
                    "tasks" => 2,
                    "hub" => 0,
                    "log" => 1

                ]) $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }
}