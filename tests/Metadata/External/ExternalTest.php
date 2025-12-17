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

namespace Valvoid\Fusion\Tests\Metadata\External;

use Valvoid\Fusion\Log\Events\Event;
use Valvoid\Fusion\Metadata\External\Category;
use Valvoid\Fusion\Metadata\External\External;
use Valvoid\Fusion\Tests\Metadata\External\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Metadata\External\Mocks\DirMock;
use Valvoid\Fusion\Tests\Metadata\External\Mocks\FileMock;
use Valvoid\Fusion\Tests\Metadata\External\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Metadata\External\Mocks\LogMock;
use Valvoid\Fusion\Tests\Test;

class ExternalTest extends Test
{
    /** @var string|array  */
    protected string|array $coverage = External::class;
    private BoxMock $box;
    private DirMock $dir;
    private GroupMock $group;
    private LogMock $log;
    private FileMock $file;
    private External $metadata;
    private array $layers = [

        // runtime helper layer
        "object" => [
            "source" => __DIR__ ."/Mocks"
        ]
    ];

    private array $content = [
        "id" => "identifier",
        "source" => ["src"],
        "dir" => "",
        "version" => "version",
        "environment" => ["environment"],
        "dependencies" => [

            // external = production only
            // fusion.json
            "production" => ["id"],
        ],
        "structure" => [
            "state" => "stateful",
            "extendables" => ["extendables"],
            "sources" => ["sources"],
            "mutables" => ["mutables"],
        ],
        "lifecycle" => [
            "copy" => "/copy.php",
            "migrate" => "/migrate.php",
            "download" => "/download.php",
            "install" => "/install.php",
        ]
    ];

    public function __construct()
    {
        $this->box = new BoxMock;
        $this->metadata = new External($this->box,$this->layers, $this->content);
        $this->log = new LogMock;
        $this->dir = new DirMock;
        $this->group = new GroupMock;
        $this->file = new FileMock;

        $this->file->exists = function ($file) {

            return str_ends_with($file, "/copy.php") ||
                str_ends_with($file, "/migrate.php") ||
                str_ends_with($file, "/download.php") ||
                str_ends_with($file, "/install.php");
        };
        $this->file->require = function ($file, mixed ...$variables) {
            if (str_ends_with($file, "/copy.php"))
                echo "copy";

            if (str_ends_with($file, "/download.php"))
                echo "download";

            if (str_ends_with($file, "/install.php"))
                echo "install";

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
        $this->testCategory();
        $this->testSource();
        $this->testStructure();
        $this->testStructureSources();
        $this->testStructureMutables();
        $this->testEnvironment();
        $this->testContent();
        $this->testLayers();
        $this->testLifecycleCopy();
        $this->testLifecycleMigrate();
        $this->testLifecycleDownload();
        $this->testLifecycleInstall();

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
            $debug !== "migrate:--dir--:--version--" ||
            $verbose !== "callback exit indicator '1'")
            $this->handleFailedTest();
    }

    public function testLifecycleDownload(): void
    {
        $verbose =
        $debug = null;

        $this->log::$verbose = function (string|Event $event) use (&$verbose) {
            $verbose = $event;
        };

        $this->log::$debug = function (string|Event $event) use (&$debug) {
            $debug = $event;
        };

        if ($this->metadata->onDownload() !== true ||
            $debug !== "download" ||
            $verbose !== "callback exit indicator '1'")
            $this->handleFailedTest();
    }

    public function testLifecycleInstall(): void
    {
        $verbose =
        $debug = null;

        $this->log::$verbose = function (string|Event $event) use (&$verbose) {
            $verbose = $event;
        };

        $this->log::$debug = function (string|Event $event) use (&$debug) {
            $debug = $event;
        };

        if ($this->metadata->onInstall() !== true ||
            $debug !== "install" ||
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
        if ($this->metadata->getDir() !== "")
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