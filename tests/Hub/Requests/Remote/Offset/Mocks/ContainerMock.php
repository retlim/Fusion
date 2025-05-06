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

namespace Valvoid\Fusion\Tests\Hub\Requests\Remote\Offset\Mocks;

use ReflectionClass;
use Valvoid\Fusion\Container\Container;
use Valvoid\Fusion\Container\Proxy\Proxy;
use Valvoid\Fusion\Hub\Requests\Remote\Curl;

/**
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ContainerMock
{
    private ReflectionClass $reflection;
    public Proxy $logic;
    public function __construct(CurlMock $curlMock)
    {
        $this->reflection = new ReflectionClass(Container::class);
        $this->logic = new class($curlMock) implements Proxy
        {
            public Curl $curl;
            public \Valvoid\Fusion\Log\Proxy\Proxy $log;

            public function __construct(CurlMock $curlMock)
            {
                $this->log = new LogMock;
                $this->curl = $curlMock;
            }

            public function get(string $class, ...$args): object
            {
                if ("Valvoid\Fusion\Log\Proxy\Proxy" === $class)
                    return $this->log;

                return $this->curl;
            }

            public function refer(string $id, string $class): void {}
            public function unset(string $class): void {}
        };

        $this->reflection->setStaticPropertyValue("instance", new class($this->logic) extends Container
        {
            public function __construct(protected Proxy $proxy) {}
        });
    }

    public function destroy(): void
    {
        $this->reflection->setStaticPropertyValue("instance", null);
    }
}