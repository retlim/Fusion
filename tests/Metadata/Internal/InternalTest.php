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

namespace Valvoid\Fusion\Tests\Metadata\Internal;

use Valvoid\Fusion\Metadata\Internal\Category;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Tests\Metadata\Internal\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Metadata\Internal\Mocks\DirMock;
use Valvoid\Fusion\Tests\Metadata\Internal\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Metadata\Internal\Mocks\LogMock;
use Valvoid\Fusion\Tests\Test;

class InternalTest extends Test
{
    /** @var string|array  */
    protected string|array $coverage = Internal::class;
    protected BoxMock $container;
    protected Internal $metadata;
    protected array $layers = [
        "layers"
    ];
    protected array $content = [
        "id" => "identifier",
        "source" => __DIR__ ."/Mocks",
        "dir" => "/",
        "version" => "version",
        "environment" => ["environment"],
        "dependencies" => [
            "local" => null,
            "development" => null,
            "production" => ["id"],
        ],
        "structure" => [
            "cache" => "cache",
            "extensions" => ["extensions"],
            "namespaces" => ["namespaces"],
            "sources" => ["sources"],
            "states" => ["states"],
            "mutables" => ["mutables"],
        ],
        "lifecycle" => [
            "copy" => "/copy.php",
            "migrate" => "/migrate.php",
            "delete" => "/delete.php",
            "update" => "/update.php"
        ]
    ];

    public function __construct()
    {
        $this->metadata = new Internal($this->layers, $this->content);
        $this->container = new BoxMock;
        $this->container->group = new GroupMock;
        $this->container->log = new LogMock;
        $this->container->dir = new DirMock;

        $this->testVersion();
        $this->testDir();
        $this->testId();
        $this->testProductionIds();
        $this->testDevelopmentIds();
        $this->testOptionalDevelopmentIds();
        $this->testLocalIds();
        $this->testOptionalLocalIds();
        $this->testCategory();
        $this->testSource();
        $this->testStructure();
        $this->testStructureCache();
        $this->testStructureSources();
        $this->testStructureNamespaces();
        $this->testStructureExtensions();
        $this->testStructureStates();
        $this->testStructureMutables();
        $this->testEnvironment();
        $this->testContent();
        $this->testLayers();
        $this->testLifecycleCopy();
        $this->testLifecycleUpdate();
        $this->testLifecycleDelete();
        $this->testLifecycleMigrate();

        $this->container::unsetInstance();
    }

    public function testLifecycleCopy(): void
    {
        if ($this->metadata->onCopy() !== true ||
            $this->container->log->event !== "copy")
            $this->handleFailedTest();
    }

    public function testLifecycleMigrate(): void
    {
        // dir + version of external package
        if ($this->metadata->onMigrate() !== true ||
            $this->container->log->event !== "migrate:/identifier:version")
            $this->handleFailedTest();
    }

    public function testLifecycleUpdate(): void
    {
        if ($this->metadata->onUpdate() !== true ||
            $this->container->log->event !== "update")
            $this->handleFailedTest();
    }

    public function testLifecycleDelete(): void
    {
        if ($this->metadata->onDelete() !== true ||
            $this->container->log->event !== "delete")
            $this->handleFailedTest();
    }

    public function testLayers(): void
    {
        if ($this->metadata->getLayers() !== $this->layers)
            $this->handleFailedTest();
    }

    public function testContent(): void
    {
        if ($this->metadata->getContent() !== $this->content)
            $this->handleFailedTest();
    }

    public function testEnvironment(): void
    {
        if ($this->metadata->getEnvironment() !== $this->content["environment"])
            $this->handleFailedTest();
    }

    public function testStructureStates(): void
    {
        if ($this->metadata->getStructureStates() !==
            $this->content["structure"]["states"])
            $this->handleFailedTest();
    }

    public function testStructureMutables(): void
    {
        if ($this->metadata->getStructureMutables() !==
            $this->content["structure"]["mutables"])
            $this->handleFailedTest();
    }

    public function testStructureExtensions(): void
    {
        if ($this->metadata->getStructureExtensions() !==
            $this->content["structure"]["extensions"])
            $this->handleFailedTest();
    }

    public function testStructureNamespaces(): void
    {
        if ($this->metadata->getStructureNamespaces() !==
            $this->content["structure"]["namespaces"])
            $this->handleFailedTest();
    }

    public function testStructureSources(): void
    {
        if ($this->metadata->getStructureSources() !==
            $this->content["structure"]["sources"])
            $this->handleFailedTest();
    }

    public function testStructureCache(): void
    {
        if ($this->metadata->getStructureCache() !==
            $this->content["structure"]["cache"])
            $this->handleFailedTest();
    }

    public function testStructure(): void
    {
        if ($this->metadata->getStructure() !== $this->content["structure"])
            $this->handleFailedTest();
    }

    public function testVersion(): void
    {
        if ($this->metadata->getVersion() !== "version")
            $this->handleFailedTest();
    }

    public function testDir(): void
    {
        if ($this->metadata->getDir() !== "/")
            $this->handleFailedTest();
    }

    public function testSource(): void
    {
        // internal source is a dir
        if ($this->metadata->getSource() !== __DIR__ ."/Mocks")
            $this->handleFailedTest();
    }

    public function testId(): void
    {
        if ($this->metadata->getId() !== "identifier")
            $this->handleFailedTest();
    }

    public function testCategory(): void
    {
        // just built
        if ($this->metadata->getCategory() !== null)
            $this->handleFailedTest();

        // lazy
        // categorize task or
        // custom
        $this->metadata->setCategory(Category::RECYCLABLE);

        if ($this->metadata->getCategory() !== Category::RECYCLABLE)
            $this->handleFailedTest();
    }

    public function testOptionalLocalIds(): void
    {
        // package has no "fusion.local.json"
        if ($this->metadata->getLocalIds() !== null)
            $this->handleFailedTest();
    }

    public function testLocalIds(): void
    {
        // package has "fusion.local.json" and
        // dependencies in it
        $metadata = new Internal([], ["dependencies" => [
            "local" => ["id"]
        ]]);

        if ($metadata->getLocalIds() !== ["id"])
            $this->handleFailedTest();
    }

    public function testOptionalDevelopmentIds(): void
    {
        if ($this->metadata->getDevelopmentIds() !== null)
            $this->handleFailedTest();
    }

    public function testDevelopmentIds(): void
    {
        $metadata = new Internal([], ["dependencies" => [
            "development" => ["id"]
        ]]);

        if ($metadata->getDevelopmentIds() !== ["id"])
            $this->handleFailedTest();
    }

    public function testProductionIds(): void
    {
        if ($this->metadata->getProductionIds() !== ["id"])
            $this->handleFailedTest();
    }
}