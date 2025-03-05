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

namespace Valvoid\Fusion\Tests\Config\Parser\Log;

use Valvoid\Fusion\Config\Parser\Log;
use Valvoid\Fusion\Tests\Config\Parser\Log\Mocks\Config\Parser;
use Valvoid\Fusion\Tests\Config\Parser\Log\Mocks\ConfigMock;
use Valvoid\Fusion\Tests\Config\Parser\Log\Mocks\SerializerMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Config log parser test.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class LogTest extends Test
{
    protected string|array $coverage = Log::class;

    public function __construct()
    {
        $configMock = new ConfigMock;

        // test parseable serializer
        $this->testDefaultSerializerConfig();
        $this->testConfiguredSerializerConfig();
        $configMock->addParser();
        $this->testConfiguredParsableSerializerConfig();
        $configMock->destroy();
    }

    public function testDefaultSerializerConfig(): void
    {
        $config = [
            "serializers" => [

                // default serializer
                "test" => SerializerMock::class
            ]
        ];

        Log::parse($config);

        $assertion = [
            "serializers" => [

                // configured serializer
                "test" => [
                    "serializer" => SerializerMock::class
                ]
            ]
        ];

        if ($config !== $assertion) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testConfiguredSerializerConfig(): void
    {
        $config = [
            "serializers" => [

                // configured serializer
                "test" => [
                    "serializer" => SerializerMock::class,
                    "whatever"
                ]
            ]
        ];

        Log::parse($config);

        // no custom parser
        if (class_exists(Parser::class, false)) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testConfiguredParsableSerializerConfig(): void
    {

        $config = [
            "serializers" => [

                // configured serializer
                "test" => [
                    "serializer" => SerializerMock::class,
                    "whatever"
                ]
            ]
        ];

        Log::parse($config);

        // passed to custom parser
        if (!class_exists(Parser::class, false)) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}