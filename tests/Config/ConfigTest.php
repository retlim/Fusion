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

namespace Valvoid\Fusion\Tests\Config;

use Throwable;
use Valvoid\Fusion\Config\Config;
use Valvoid\Fusion\Tests\Config\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Config\Mocks\ConfigMock;
use Valvoid\Fusion\Tests\Test;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class ConfigTest extends Test
{
    protected string|array $coverage = Config::class;
    private BoxMock $box;

    public function __construct()
    {
        $this->box = new BoxMock;

        $this->testStaticInterface();

        $this->box::unsetInstance();
    }

    public function testStaticInterface(): void
    {
        try {
            $calls = [];
            $config = new ConfigMock;
            $config->get = function () use (&$calls) {
                $calls[] = "get";
                return false;
            };

            $config->lazy = function () use (&$calls) {
                $calls[] = "lazy";
                return [];
            };

            $config->has = function () use (&$calls) {
                $calls[] = "has";
                return false;
            };

            $this->box->get = function () use ($config) {
                return $config;
            };

            Config::get();
            Config::getLazy();
            Config::hasLazy("");

            if ($calls !== ["get", "lazy", "has"])
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }
}