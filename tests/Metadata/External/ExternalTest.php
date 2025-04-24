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

namespace Valvoid\Fusion\Tests\Metadata\External;

use Valvoid\Fusion\Metadata\External\Category;
use Valvoid\Fusion\Metadata\External\External;
use Valvoid\Fusion\Tests\Metadata\External\Mocks\ContainerMock;
use Valvoid\Fusion\Tests\Test;

/**
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ExternalTest extends Test
{
    /** @var string|array  */
    protected string|array $coverage = External::class;

    /** @var ContainerMock  */
    protected ContainerMock $container;

    /** @var External  */
    protected External $metadata;

    /** @var array  */
    protected array $layers = [

        // runtime helper layer
        "object" => [
            "source" => __DIR__ ."/Mocks"
        ]
    ];

    /** @var array  */
    protected array $content = [
        "id" => "identifier",
        "source" => ["src"],
        "dir" => "/",
        "version" => "version",
        "environment" => ["environment"],
        "dependencies" => [

            // external = production only
            // fusion.json
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
            "migrate" => "/migrate.php"
        ]
    ];

    public function __construct()
    {
        $this->metadata = new External($this->layers, $this->content);
        $this->container = new ContainerMock;

        $this->testVersion();
        $this->testDir();
        $this->testId();
        $this->testProductionIds();
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
        // dir + version of internal package
        if ($this->metadata->onMigrate() !== true ||
            $this->container->proxy->log->event !==
            "migrate:/home/retlim/Desktop/dev/fusion/php/code/tests" .
            "/Metadata/External/Mocks:version")
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
        if ($this->metadata->getSource() !== ["src"])
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
        $this->metadata->setCategory(Category::DOWNLOADABLE);

        if ($this->metadata->getCategory() !== Category::DOWNLOADABLE)
            $this->handleFailedTest();
    }

    public function testProductionIds(): void
    {
        if ($this->metadata->getProductionIds() !== ["id"])
            $this->handleFailedTest();
    }
}