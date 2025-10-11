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

namespace Valvoid\Fusion\Tests\Metadata\Internal\Builder;

use Throwable;
use Valvoid\Fusion\Metadata\Internal\Builder;
use Valvoid\Fusion\Tests\Metadata\Internal\Builder\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Metadata\Internal\Builder\Mocks\BusMock;
use Valvoid\Fusion\Tests\Test;

/**
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class BuilderTest extends Test
{
    /** @var string|array  */
    protected string|array $coverage = Builder::class;
    protected BoxMock $container;
    protected Builder $builder;

    public function __construct()
    {
        $this->container = new BoxMock;
        $this->container->bus = new BusMock;
        $this->builder = new Builder("/dir", "/src");

        // overlay metadata
        $this->testProductionLayer();
        $this->testDevelopmentLayer();
        $this->testLocalLayer();
        $this->testBotLayer();
        $this->testMetadata();

        $this->container::unsetInstance();
    }

    public function testLocalLayer(): void
    {
        $this->builder->addLocalLayer(
            ["structure" => [
                "/dependencies" => [
                    "api/path2/4.5.6"
                ]
            ]],
            "/fusion.local.php"
        );
    }

    public function testDevelopmentLayer(): void
    {
        $this->builder->addDevelopmentLayer(
            ["structure" => [
                "/dependencies" => [
                    "api/path1/1.2.3"
                ]
            ]],
            "/fusion.dev.php"
        );
    }

    public function testBotLayer(): void
    {
        // persist offset
        $this->builder->addBotLayer(
            ["version" => "2.3.4"],
            "/fusion.bot.php"
        );
    }

    public function testProductionLayer(): void
    {
        try {
            $metadata = [
                "id" => "path",
                "version" => "1.0.0",
                "environment" => ["environment"],
                "structure" => [
                    "/cache" => "cache"
                ]
            ];

            $this->builder->addProductionLayer(
                json_encode($metadata),
                "fusion.json"
            );

            return;

        } catch (Throwable $e) {
            $this->handleFailedTest();
        }
    }

    public function testMetadata(): void
    {
        if ($this->builder->getMetadata()->getContent() !== [
                "id" => "path",
                "version" => "2.3.4",
                "environment" => [
                    "environment",
                    "php" => [
                        "modules" => []
                    ]
                ],
                "structure" => [
                    "cache" => "/cache",
                    "sources" => [
                        "/dependencies" => [
                            "api/path1/1.2.3",
                            "api/path2/4.5.6"
                        ]
                    ],
                    "extensions" => [],
                    "mappings" => [],
                    "namespaces" => [],
                    "states" => [],
                    "mutables" => []
                ],
                "dir" => "/dir/path",
                "source" => "/src",
                "dependencies" => [
                    "production" => [],
                    "development" => ["path1"],
                    "local" => ["path2"]
                ]
            ])
            $this->handleFailedTest();
    }
}