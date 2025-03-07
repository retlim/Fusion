<?php
/**
 * Fusion. A package manager for PHP-based projects.
 * Copyright Valvoid
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

namespace Valvoid\Fusion\Tests\Hub;

use ReflectionException;
use Valvoid\Fusion\Container\Proxy\Logic;
use Valvoid\Fusion\Hub\Hub;
use Valvoid\Fusion\Tests\Test;

/**
 * Hub test.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class HubTest extends Test
{
    protected string|array $coverage = Hub::class;

    private Hub $hub;

    public function __construct()
    {
        try {
            $configMock = new ConfigMock;
            $this->hub = (new Logic)->get(Hub::class);

            $this->testInstanceDestruction();

            $configMock->destroy();
            $this->hub->destroy();

        } catch (ReflectionException $exception) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testInstanceDestruction(): void
    {
        $instance = $this->hub;
        $this->hub->destroy();
        $this->hub = (new Logic)->get(Hub::class);

        // assert different instances
        if ($instance === $this->hub) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}