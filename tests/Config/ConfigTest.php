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

namespace Valvoid\Fusion\Tests\Config;

use Valvoid\Fusion\Config\Config;
use Valvoid\Fusion\Tests\Config\Mocks\ContainerMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Config test.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ConfigTest extends Test
{
    protected string|array $coverage = Config::class;
    private ContainerMock $container;

    public function __construct()
    {
        $this->container = new ContainerMock;

        // static
        $this->testStaticInterface();
        $this->container->destroy();
    }

    public function testStaticInterface(): void
    {
        Config::get();
        Config::getLazy();
        Config::hasLazy("");

        // static functions connected to same non-static functions
        if ($this->container->logic->config->calls !== [
            "get",
            "getLazy",
            "hasLazy"]) {

            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}