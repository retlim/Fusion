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

use Valvoid\Fusion\Log\Events\Event;
use Valvoid\Fusion\Metadata\Internal\Category;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Tests\Metadata\Internal\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Metadata\Internal\Mocks\DirMock;
use Valvoid\Fusion\Tests\Metadata\Internal\Mocks\FileMock;
use Valvoid\Fusion\Tests\Metadata\Internal\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Metadata\Internal\Mocks\LogMock;
use Valvoid\Fusion\Tests\Test;

class InternalTest extends Test
{
    /** @var string|array  */
    protected string|array $coverage = Internal::class;
    protected BoxMock $box;
    protected Internal $metadata;

    private DirMock $dir;
    private GroupMock $group;
    private LogMock $log;
    private FileMock $file;

    protected array $layers = [
        "layers"
    ];
    protected array $content = [
        "id" => "identifier",
        "source" => "--source--",
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
        $this->box = new BoxMock;
        $this->metadata = new Internal($this->box,$this->layers, $this->content);
        $this->log = new LogMock;
        $this->dir = new DirMock;
        $this->group = new GroupMock;
        $this->file = new FileMock;

        $this->file->exists = function ($file) {

            return str_ends_with($file, "/copy.php") ||
                str_ends_with($file, "/migrate.php") ||
                str_ends_with($file, "/update.php") ||
                str_ends_with($file, "/delete.php");
        };
        $this->file->require = function ($file, mixed ...$variables) {
            if (str_ends_with($file, "/copy.php"))
                echo "copy";

            if (str_ends_with($file, "/update.php"))
                echo "update";

            if (str_ends_with($file, "/delete.php"))
                echo "delete";

            if (str_ends_with($file, "/migrate.php")) {
                extract(...$variables);
                echo "migrate:". ( $dir ?? "") .":". ( $version ?? "no ver");
            }

            return true;
        };

        $this->box->get = function (string $class, ...$args) {
            if ($class == "Valvoid\Fusion\Dir\Dir")
                return $this->dir;

            if ($class == "Valvoid\Fusion\Wrappers\File")
                return $this->file;

            if ($class == "Valvoid\Fusion\Log\Log")
                return $this->log;

            if ($class == "Valvoid\Fusion\Tasks\Group")
                return $this->group;
        };

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
        $this->testStructureSources();
        $this->testStructureMutables();
        $this->testEnvironment();
        $this->testContent();
        $this->testLayers();
        $this->testLifecycleCopy();
        $this->testLifecycleUpdate();
        $this->testLifecycleDelete();
        $this->testLifecycleMigrate();

        $this->log::$verbose = null;
        $this->log::$debug = null;
        $this->box::unsetInstance();
    }

    public function testLifecycleCopy(): void
    {
        $verbose =
        $debug = null;

        $this->log::$verbose = function (string|Event $event) use (&$verbose) {
            $verbose = $event;
        };

        $this->log::$debug = function (string|Event $event) use (&$debug) {
            $debug = $event;
        };

        if ($this->metadata->onCopy() !== true ||
            $debug !== "copy" ||
            $verbose !== "callback exit indicator '1'")
            $this->handleFailedTest();
    }

    public function testLifecycleMigrate(): void
    {
        $verbose =
        $debug = null;

        $this->log::$verbose = function (string|Event $event) use (&$verbose) {
            $verbose = $event;
        };

        $this->log::$debug = function (string|Event $event) use (&$debug) {
            $debug = $event;
        };

        if ($this->metadata->onMigrate() !== true ||
            $debug !== "migrate:/identifier:version" ||
            $verbose !== "callback exit indicator '1'")
            $this->handleFailedTest();
    }

    public function testLifecycleUpdate(): void
    {
        $verbose =
        $debug = null;

        $this->log::$verbose = function (string|Event $event) use (&$verbose) {
            $verbose = $event;
        };

        $this->log::$debug = function (string|Event $event) use (&$debug) {
            $debug = $event;
        };

        if ($this->metadata->onUpdate() !== true ||
            $debug !== "update" ||
            $verbose !== "callback exit indicator '1'")
            $this->handleFailedTest();
    }

    public function testLifecycleDelete(): void
    {
        $verbose =
        $debug = null;

        $this->log::$verbose = function (string|Event $event) use (&$verbose) {
            $verbose = $event;
        };

        $this->log::$debug = function (string|Event $event) use (&$debug) {
            $debug = $event;
        };

        if ($this->metadata->onDelete() !== true ||
            $debug !== "delete" ||
            $verbose !== "callback exit indicator '1'")
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

    public function testStructureMutables(): void
    {
        if ($this->metadata->getStructureMutables() !==
            $this->content["structure"]["mutables"])
            $this->handleFailedTest();
    }

    public function testStructureSources(): void
    {
        if ($this->metadata->getStructureSources() !==
            $this->content["structure"]["sources"])
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
        if ($this->metadata->getSource() !== "--source--")
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
        $metadata = new Internal($this->box,[], ["dependencies" => [
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
        $metadata = new Internal($this->box,[], ["dependencies" => [
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