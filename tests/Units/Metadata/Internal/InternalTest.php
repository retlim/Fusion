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

namespace Valvoid\Fusion\Tests\Units\Metadata\Internal;

use Valvoid\Box\Box;
use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Metadata\Internal\Category;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Reflex\Test\Wrapper;

class InternalTest extends Wrapper
{
    private Internal $metadata;
    private array $layers = [
        "layers"
    ];

    private array $content = [
        "id" => "identifier",
        "source" => "--source--",
        "dir" => "#",
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

    public function __construct(Box $box)
    {
        parent::__construct($box);

        $box = $this->createMock(Box::class);
        $this->metadata = new Internal(
            box: $box,
            layers: $this->layers,
            content: $this->content
        );
    }

    public function testLayers(): void
    {
        $this->validate($this->metadata->getLayers())
            ->as($this->layers);
    }

    public function testContent(): void
    {
        $this->validate($this->metadata->getContent())
            ->as($this->content);
    }

    public function testEnvironment(): void
    {
        $this->validate($this->metadata->getEnvironment())
            ->as($this->content["environment"]);
    }

    public function testStructureMutables(): void
    {
        $this->validate($this->metadata->getStructureMutables())
            ->as($this->content["structure"]["mutables"]);
    }

    public function testStructureSources(): void
    {
        $this->validate($this->metadata->getStructureSources())
            ->as($this->content["structure"]["sources"]);
    }

    public function testStructure(): void
    {
        $this->validate($this->metadata->getStructure())
            ->as($this->content["structure"]);
    }

    public function testVersion(): void
    {
        $this->validate($this->metadata->getVersion())
            ->as("version");
    }

    public function testDir(): void
    {
        $this->validate($this->metadata->getDir())
            ->as("#");
    }

    public function testSource(): void
    {
        // internal source is a dir
        $this->validate($this->metadata->getSource())
            ->as("--source--");
    }

    public function testId(): void
    {
        $this->validate($this->metadata->getId())
            ->as("identifier");
    }

    public function testCategory(): void
    {
        // just built
        $this->validate($this->metadata->getCategory())
            ->asNull();

        // lazy
        // categorize task or
        // custom
        $this->metadata->setCategory(Category::RECYCLABLE);

        $this->validate($this->metadata->getCategory())
            ->as(Category::RECYCLABLE);
    }

    public function testOptionalLocalIds(): void
    {
        // package has no "fusion.local.json"
        $this->validate($this->metadata->getLocalIds())
            ->asNull();
    }

    public function testOptionalDevelopmentIds(): void
    {
        $this->validate($this->metadata->getDevelopmentIds())
            ->asNull();
    }

    public function testProductionIds(): void
    {
        $this->validate($this->metadata->getProductionIds())
            ->as(["id"]);
    }

    public function testLifecycleCopy(): void
    {
        $box = $this->recycleMock(Box::class);
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $log = $this->createMock(Log::class);

        $box->fake("get")
            ->expect(class: Dir::class)
            ->return($dir)
            ->expect(class: File::class)
            ->return($file)
            ->expect(class: Log::class)
            ->return($log);

        $dir->fake("getStateDir")
            ->return("#0");

        $file->fake("exists")
            ->expect(file: "#0#/copy.php") // + empty dir
            ->return(true)
            ->fake("require")
            ->expect(file: "#0#/copy.php", variables: [[]])
            ->return(1);

        $log->fake("verbose")
            ->expect(event: "callback exit indicator '1'")
            ->fake("debug")
            ->expect(event: "");

        $this->validate($this->metadata->onCopy())
            ->as(true);
    }

    public function testLifecycleUpdate(): void
    {
        $box = $this->recycleMock(Box::class);
        $file = $this->resetMock(File::class);
        $log = $this->recycleMock(Log::class);

        $box->fake("get")
            ->expect(class: File::class)
            ->return($file)
            ->expect(class: Log::class)
            ->return($log);

        $file->fake("exists")
            ->expect(file: "--source--/update.php")
            ->return(true)
            ->fake("require")
            ->expect(file: "--source--/update.php", variables: [[]])
            ->return(true);

        $this->validate($this->metadata->onUpdate())
            ->as(true);
    }

    public function testLifecycleDelete(): void
    {
        $box = $this->recycleMock(Box::class);
        $file = $this->resetMock(File::class);
        $log = $this->recycleMock(Log::class);

        $box->fake("get")
            ->expect(class: File::class)
            ->return($file)
            ->expect(class: Log::class)
            ->return($log);

        $file->fake("exists")
            ->expect(file: "--source--/delete.php")
            ->return(true)
            ->fake("require")
            ->expect(file: "--source--/delete.php", variables: [[]])
            ->return(true);

        $this->validate($this->metadata->onDelete())
            ->as(true);
    }

    public function testLifecycleMigrate(): void
    {
        $box = $this->recycleMock(Box::class);
        $group = $this->createMock(Group::class);
        $internal = $this->createMock(Internal::class);
        $dir = $this->createMock(Dir::class);
        $file = $this->createMock(File::class);
        $log = $this->createMock(Log::class);

        $box->fake("get")
            ->expect(class: Group::class)
            ->return($group)
            ->expect(class: Dir::class)
            ->return($dir)
            ->expect(class: File::class)
            ->return($file)
            ->expect(class: Log::class)
            ->return($log);

        $group->fake("getExternalMetas")
            ->return(["identifier" => $internal]);

        $dir->fake("getPackagesDir")
            ->return("#0");

        $internal->fake("getVersion")
            ->return("#v");

        $file->fake("exists")
            ->expect(file: "--source--/migrate.php")
            ->return(true)
            ->fake("require")
            ->return(true)
            ->expect(file: "--source--/migrate.php",
                variables: [["dir" => "#0/identifier", "version" => "#v"]]);

        $log->fake("verbose")
            ->expect(event: "callback exit indicator '1'")
            ->fake("debug")
            ->expect(event: "");

        $this->validate($this->metadata->onMigrate())
            ->as(true);
    }
}