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

namespace Valvoid\Fusion\Tests\Metadata\External\Builder;

use Throwable;
use Valvoid\Fusion\Metadata\External\Builder;
use Valvoid\Fusion\Tests\Metadata\External\Builder\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Metadata\External\Builder\Mocks\BusMock;
use Valvoid\Fusion\Tests\Test;

/**
 *
 * @copyright Valvoid
 * @license GNU GPLv3
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
        $this->builder = new Builder("/dir", "api/path/1.0.0");

        $this->testId();
        $this->testRawDir();

        // pattern reference
        $this->testSource();

        // absolute/pointer top reference
        $this->testNormalizeReference();
        $this->testProductionLayer();
        $this->testMetadata();

        $this->container::unsetInstance();
    }

    public function testProductionLayer(): void
    {
        try {
            $metadata = [
                "id" => "path",
                "version" => "2.3.4",
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
                    "sources" => [],
                    "extensions" => [],
                    "namespaces" => [],
                    "states" => []
                ],
                "source" => [
                    "api" => "api",
                    "path" => "/path",
                    "prefix" => "",
                    "reference" => "2.3.4"
                ],
                "dir" => "/dir/path",
                "dependencies" => [
                    "production" => []
                ]
            ])
            $this->handleFailedTest();
    }

    public function testNormalizeReference(): void
    {
        $source = [
            "api" => "api",
            "path" => "/path",
            "prefix" => "",
            "reference" => "2.3.4"
        ];

        $this->builder->normalizeReference("2.3.4");

        if ($this->builder->getNormalizedSource() !== $source ||
            $this->builder->getParsedSource() !== $source)
            $this->handleFailedTest();
    }

    public function testSource(): void
    {
        $source = [
            "api" => "api",
            "path" => "/path",
            "prefix" => "",
            "reference" => [[
                "build" => "",
                "release" => "",
                "major" => "1",
                "minor" => "0",
                "patch" => "0",
                "sign" => ""
            ]]
        ];

        if ($this->builder->getNormalizedSource() !== $source ||
            $this->builder->getParsedSource() !== $source)
            $this->handleFailedTest();
    }

    public function testId(): void
    {
        if ($this->builder->getId() !== "path")
            $this->handleFailedTest();
    }

    public function testRawDir(): void
    {
        if ($this->builder->getRawDir() !== "/dir")
            $this->handleFailedTest();
    }
}