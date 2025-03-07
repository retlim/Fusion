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

namespace Valvoid\Fusion\Tests\Bus;

use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Cache;
use Valvoid\Fusion\Bus\Events\Config;
use Valvoid\Fusion\Bus\Events\Metadata;
use Valvoid\Fusion\Bus\Events\Root;
use Valvoid\Fusion\Container\Proxy\Logic;
use Valvoid\Fusion\Tests\Test;

/**
 * Bus test.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class BusTest extends Test
{
    protected string|array $coverage = [
        Bus::class,

        // exclude data wrapper since
        // nothing to test
        Cache::class,
        Config::class,
        Metadata::class,
        Root::class
    ];

    private Bus $bus;

    public function __construct()
    {
        $this->bus = (new Logic)->get(Bus::class);

        $this->testInstanceDestruction();

        (new Logic)->unset(Bus::class);
    }


    public function testInstanceDestruction(): void
    {
        $instance = $this->bus;
        (new Logic)->unset(Bus::class);
        $this->bus = (new Logic)->get(Bus::class);

        // assert different instances
        if ($instance === $this->bus) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}