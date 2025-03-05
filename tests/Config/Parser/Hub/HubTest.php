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

namespace Valvoid\Fusion\Tests\Config\Parser\Hub;

use Valvoid\Fusion\Config\Parser\Hub;
use Valvoid\Fusion\Tests\Config\Parser\Hub\Mocks\ApiMock;
use Valvoid\Fusion\Tests\Config\Parser\Hub\Mocks\Config\Parser;
use Valvoid\Fusion\Tests\Config\Parser\Hub\Mocks\ConfigMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Config hub parser test.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class HubTest extends Test
{
    protected string|array $coverage = Hub::class;

    public function __construct()
    {
        $configMock = new ConfigMock();

        // test parseable api
        $this->testDefaultApiConfig();
        $this->testConfiguredApiConfig();
        $configMock->addParser();
        $this->testConfiguredParsableApiConfig();
        $configMock->destroy();
    }

    public function testDefaultApiConfig(): void
    {
        $config = [
            "apis" => [

                // default api
                "test" => ApiMock::class
            ]
        ];

        Hub::parse($config);

        $assertion = [
            "apis" => [

                // configured api
                "test" => [
                    "api" => ApiMock::class
                ]
            ]
        ];

        if ($config !== $assertion) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testConfiguredApiConfig(): void
    {
        $config = [
            "apis" => [

                // configured api
                "test" => [
                    "api" => ApiMock::class,
                    "whatever"
                ]
            ]
        ];

        Hub::parse($config);

        // no custom parser
        if (class_exists(Parser::class, false)) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testConfiguredParsableApiConfig(): void
    {
        $config = [
            "apis" => [

                // configured api
                "test" => [
                    "api" => ApiMock::class,
                    "whatever"
                ]
            ]
        ];

        Hub::parse($config);

        // passed to custom parser
        if (!class_exists(Parser::class, false)) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}