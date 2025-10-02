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

namespace Valvoid\Fusion\Tests\Tasks\Replicate\Config;

use Valvoid\Fusion\Tasks\Replicate\Config\Normalizer;
use Valvoid\Fusion\Tests\Test;

/**
 * Config normalizer test.
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class NormalizerTest extends Test
{
    protected string|array $coverage = Normalizer::class;

    public function __construct()
    {
        $this->testDefaultValues();
        $this->testDefaultSource();
        $this->testDefaultPhpVersion();
    }

    public function testDefaultValues(): void
    {
        $config = [];
        $assertion = [
            "source" => false,
            "environment" => [
                "php" => [
                    "version" => [
                        "major" => PHP_MAJOR_VERSION,
                        "minor" => PHP_MINOR_VERSION,
                        "patch" => PHP_RELEASE_VERSION,
                        "release" => "",
                        "build" => ""
                    ]
                ]
            ]
        ];

        Normalizer::normalize([], $config);

        if ($config !== $assertion)
            $this->handleFailedTest();
    }

    public function testDefaultSource(): void
    {
        $config = [
            "environment" => [
                "php" => [
                    "version" => true // lock
                ]
            ]];

        $assertion = [
            "source" => false,
            "environment" => [
                "php" => [
                    "version" => true
                ]
            ]
        ];

        Normalizer::normalize([], $config);

        if ($config != $assertion)
            $this->handleFailedTest();
    }

    public function testDefaultPhpVersion(): void
    {
        $config = [
            "source" => "", // lock
        ];
        $assertion = [
            "source" => "",
            "environment" => [
                "php" => [
                    "version" => [
                        "major" => PHP_MAJOR_VERSION,
                        "minor" => PHP_MINOR_VERSION,
                        "patch" => PHP_RELEASE_VERSION,
                        "release" => "",
                        "build" => ""
                    ]
                ]
            ]
        ];

        Normalizer::normalize([], $config);

        if ($config != $assertion)
            $this->handleFailedTest();
    }
}