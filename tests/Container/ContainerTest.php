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

namespace Valvoid\Fusion\Tests\Container;

use Valvoid\Fusion\Container\Container;
use Valvoid\Fusion\Container\Proxy\Logic;
use Valvoid\Fusion\Tests\Container\Mocks\ArgMock;
use Valvoid\Fusion\Tests\Container\Mocks\DependencyMock;
use Valvoid\Fusion\Tests\Container\Mocks\NonPublicMock;
use Valvoid\Fusion\Tests\Container\Mocks\PublicMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Container test.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ContainerTest extends Test
{
    protected string|array $coverage = [
        Container::class,
        Logic::class
    ];

    private Logic $logic;

    public function __construct()
    {
        $this->logic = new Logic;

        $this->testPublicInstance();
        $this->testNonPublicInstance();
        $this->testArguments();
        $this->testNestedDependency();
    }

    public function testPublicInstance(): void
    {
        $instance = $this->logic->get(PublicMock::class);

        if ($instance instanceof PublicMock)
            return;

        echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

        $this->result = false;
    }

    public function testNonPublicInstance(): void
    {
        $instance = $this->logic->get(NonPublicMock::class);

        if ($instance instanceof NonPublicMock)
            return;

        echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

        $this->result = false;
    }

    public function testArguments(): void
    {
        $instance = $this->logic->get(ArgMock::class, value: "test");

        if ($instance instanceof ArgMock &&
            $instance->value === "test")
            return;

        echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

        $this->result = false;
    }

    public function testNestedDependency(): void
    {
        $instance = $this->logic->get(DependencyMock::class, value: "test");

        if ($instance instanceof DependencyMock &&
            $instance->mock instanceof ArgMock &&
            $instance->mock->value === "test")
            return;

        echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

        $this->result = false;
    }
}