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

namespace Valvoid\Fusion\Tests\Config\Parser\Log;

use Valvoid\Fusion\Config\Parser\Log;
use Valvoid\Fusion\Tests\Config\Parser\Log\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Config\Parser\Log\Mocks\Config\Parser;
use Valvoid\Fusion\Tests\Config\Parser\Log\Mocks\ConfigMock;
use Valvoid\Fusion\Tests\Config\Parser\Log\Mocks\SerializerMock;
use Valvoid\Fusion\Tests\Test;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class LogTest extends Test
{
    protected string|array $coverage = Log::class;

    public function __construct()
    {
        $config = new ConfigMock;
        $box = new BoxMock;

        $config->get = 0;
        $config->lazy = [];
        $config->has = false;
        $box->get = $config;

        // test parseable serializer
        $this->testDefaultSerializerConfig();
        $this->testConfiguredSerializerConfig();

        $config->has = true;

        $this->testConfiguredParsableSerializerConfig();

        $box::unsetInstance();
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

        if ($config !== $assertion)
            $this->handleFailedTest();
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
        if (class_exists(Parser::class, false))
            $this->handleFailedTest();
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
        if (!class_exists(Parser::class, false))
            $this->handleFailedTest();
    }
}