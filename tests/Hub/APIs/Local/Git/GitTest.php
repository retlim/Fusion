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

namespace Valvoid\Fusion\Tests\Hub\APIs\Local\Git;

use Valvoid\Fusion\Hub\APIs\Local\Git\Git;
use Valvoid\Fusion\Hub\Responses\Local\Offset;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Tests\Hub\APIs\Local\Git\Mocks\ContainerMock;
use Valvoid\Fusion\Tests\Test;
use Valvoid\Fusion\Hub\Responses\Local\File as FileResponse;
use Valvoid\Fusion\Hub\Responses\Local\References as ReferencesResponse;
use Valvoid\Fusion\Hub\Responses\Local\Archive as ArchiveResponse;
use Valvoid\Fusion\Wrappers\Program;

/**
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class GitTest extends Test
{
    protected Git $api;
    protected ContainerMock $container;
    protected string|array $coverage = [
        Git::class,

        // ballast
        Program::class
    ];

    public function __construct()
    {
        $this->container = new ContainerMock;
        $this->api = new Git("/root", []);

        $this->testRoot();
        $this->testFileLocation();
        $this->testContent();
        $this->testOffset();
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
                $references->getEntries() !== [
                    "1.2.3",
                    "2.3.4",
                    "3.4.5",
                ])
                $this->handleFailedTest();

        } catch (Error) {
            $this->handleFailedTest();
        }
    }

    public function testOffset(): void
    {
        try {
            $offset = $this->api->getOffset("/-", "main");

            if (!($offset instanceof Offset) ||
                $offset->getId() !== "7fe3f596be4e7a")
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