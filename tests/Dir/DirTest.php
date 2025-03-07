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

namespace Valvoid\Fusion\Tests\Dir;

use ReflectionException;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Container\Proxy\Logic;
use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Tests\Test;

/**
 * Hub test.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class DirTest extends Test
{
    protected string|array $coverage = Dir::class;

    private Dir $dir;

    public function __construct()
    {
        try {
            $configMock = new ConfigMock;
            $bus = (new Logic)->get(Bus::class);
            $this->dir = (new Logic)->get(Dir::class);

            $this->testInstanceDestruction();

            $configMock->destroy();
            $this->dir->destroy();
            (new Logic)->unset(Bus::class);

        } catch (ReflectionException $exception) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testInstanceDestruction(): void
    {
        $instance = $this->dir;
        $this->dir->destroy();
        $this->dir = (new Logic)->get(Dir::class);

        // assert different instances
        if ($instance === $this->dir) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}