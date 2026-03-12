<?php
/*
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
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Valvoid\Fusion\Tests\Units\Tasks\Build\Config;

use Valvoid\Fusion\Tasks\Build\Config\Normalizer;
use Valvoid\Reflex\Test\Wrapper;

class NormalizerTest extends Wrapper
{
    public function testDefaultValues(): void
    {
        $config = [];
        $normalizer = new Normalizer;

        $normalizer->normalize([], $config);
        $this->validate($config)
            ->as([
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
            ]);
    }

    public function testDefaultSource(): void
    {
        $normalizer = new Normalizer;
        $config = [
            "environment" => [
                "php" => [
                    "version" => true // lock
                ]
            ]];

        $normalizer->normalize([], $config);
        $this->validate($config)
            ->as([
                "environment" => [
                    "php" => [
                        "version" => true
                    ]
                ],
                "source" => false,
            ]);
    }

    public function testDefaultPhpVersion(): void
    {
        $normalizer = new Normalizer;
        $config = [
            "source" => "", // lock
        ];

        $normalizer->normalize([], $config);
        $this->validate($config)
            ->as([
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
            ]);
    }
}