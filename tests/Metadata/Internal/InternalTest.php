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

namespace Valvoid\Fusion\Tests\Metadata\Internal;

use Valvoid\Fusion\Metadata\Internal\Category;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Tests\Metadata\Internal\Mocks\ContainerMock;
use Valvoid\Fusion\Tests\Test;

/**
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class InternalTest extends Test
{
    /** @var string|array  */
    protected string|array $coverage = Internal::class;

    /** @var ContainerMock  */
    protected ContainerMock $container;

    /** @var Internal  */
    protected Internal $metadata;

    /** @var array  */
    protected array $layers = [
        "layers"
    ];

    /** @var array  */
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
        $this->container = new ContainerMock;

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
        $this->testEnvironment();
        $this->testContent();
        $this->testLayers();
        $this->testLifecycleCopy();
        $this->testLifecycleUpdate();
        $this->testLifecycleDelete();
        $this->testLifecycleMigrate();

        $this->container->destroy();
    }

    public function testLifecycleCopy(): void
    {
        if ($this->metadata->onCopy() !== true ||
            $this->container->proxy->log->event !== "copy")
            $this->handleFailedTest();
    }

    public function testLifecycleMigrate(): void
    {
        if ($this->metadata->onMigrate() !== true ||
            $this->container->proxy->log->event !== "migrate:/identifier:version")
            $this->handleFailedTest();
    }

    public function testLifecycleUpdate(): void
    {
        if ($this->metadata->onUpdate() !== true ||
            $this->container->proxy->log->event !== "update")
            $this->handleFailedTest();
    }

    public function testLifecycleDelete(): void
    {
        if ($this->metadata->onDelete() !== true ||
            $this->container->proxy->log->event !== "delete")
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