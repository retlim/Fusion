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

namespace Valvoid\Fusion\Tests\Hub\APIs\Local\Dir;

use Valvoid\Fusion\Hub\APIs\Local\Dir\Dir;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Tests\Hub\APIs\Local\Dir\Mocks\ContainerMock;
use Valvoid\Fusion\Tests\Test;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Fusion\Hub\Responses\Local\File as FileResponse;
use Valvoid\Fusion\Hub\Responses\Local\References as ReferencesResponse;
use Valvoid\Fusion\Hub\Responses\Local\Archive as ArchiveResponse;

/**
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class DirTest extends Test
{
    protected Dir $api;
    protected ContainerMock $container;
    protected string|array $coverage = [
        Dir::class,

        // ballast
        File::class
    ];

    public function __construct()
    {
        $this->container = new ContainerMock;
        $this->api = new Dir("/root", []);

        $this->testRoot();
        $this->testFileLocation();
        $this->testContent();
        $this->testReferences();
        $this->testArchive();

        $this->container->destroy();
    }

    public function testRoot(): void
    {
        if ($this->api->getRoot() !== "/root")
            $this->handleFailedTest();
    }

    public function testFileLocation(): void
    {
        if ($this->api->getFileLocation(
            "/-", "_", "/#") !== "/root/-/# | _")
            $this->handleFailedTest();
    }

    public function testContent(): void
    {
        try {
            $content = $this->api->getFileContent("/-", "_", "/#");

            if (!($content instanceof FileResponse) ||

                // raw production metadata
                $content->getContent() !== '{"version":"_"}')
                $this->handleFailedTest();

        } catch (Error) {
            $this->handleFailedTest();
        }
    }

    public function testReferences(): void
    {
        try {
            $references = $this->api->getReferences("/-");

            if (!($references instanceof ReferencesResponse) ||
                $references->getEntries() !== ["_"])
                $this->handleFailedTest();

            if ($this->container->logic->file->file !== "/root/-/fusion.json")
                $this->handleFailedTest();

        } catch (Error) {
            $this->handleFailedTest();
        }
    }

    public function testArchive(): void
    {
        try {
            $archive = $this->api->createArchive("/nested","_","/-");

            if (!($archive instanceof ArchiveResponse) ||
                $archive->getFile() !== "/-/archive.zip")
                $this->handleFailedTest();

        } catch (Error) {
            $this->handleFailedTest();
        }
    }
}