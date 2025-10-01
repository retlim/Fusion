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

namespace Valvoid\Fusion\Tests\Config\Parser\Tasks;

use Valvoid\Fusion\Config\Parser\Tasks;
use Valvoid\Fusion\Tasks\Inflate\Inflate;
use Valvoid\Fusion\Tests\Config\Parser\Tasks\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Config\Parser\Tasks\Mocks\Config\Parser;
use Valvoid\Fusion\Tests\Config\Parser\Tasks\Mocks\ConfigMock;
use Valvoid\Fusion\Tests\Config\Parser\Tasks\Mocks\TaskMock;
use Valvoid\Fusion\Tests\Test;

/**
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class TasksTest extends Test
{
    protected string|array $coverage = Tasks::class;

    public function __construct()
    {
        $config = new ConfigMock;
        $box = new BoxMock;

        $config->get = 0;
        $config->lazy = [];
        $config->has = false;
        $box->get = $config;

        // test parseable task
        $this->testDefaultTaskConfig();
        $this->testConfiguredTaskConfig();

        $config->has = true;

        $this->testConfiguredParsableTaskConfig();
        $box::unsetInstance();
    }

    public function testDefaultTaskConfig(): void
    {
        $config = [

            // default task
            "test" => Inflate::class
        ];

        Tasks::parse($config);

        $assertion = [

            // configured task
            "test" => [
                "task" => Inflate::class
            ]
        ];

        if ($config != $assertion)
            $this->handleFailedTest();
    }

    public function testConfiguredTaskConfig(): void
    {
        $config = [

            // configured task
            "test" => [
                "task" => TaskMock::class,
                "whatever"
            ]
        ];

        Tasks::parse($config);

        // no custom parser
        if (Parser::$config !== [])
            $this->handleFailedTest();
    }

    public function testConfiguredParsableTaskConfig(): void
    {
        $config = [

            // configured task
            "test" => [
                "task" => TaskMock::class,
                "whatever"
            ]
        ];

        Tasks::parse($config);

        // passed to custom parser
        if (Parser::$config !== $config["test"])
            $this->handleFailedTest();
    }
}