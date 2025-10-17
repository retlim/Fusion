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
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Valvoid\Fusion\Tests\Hub\Requests\Local\Archive;

use Throwable;
use Valvoid\Fusion\Hub\Requests\Local\Archive;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Errors\Request;
use Valvoid\Fusion\Tests\Hub\Requests\Local\Archive\Mocks\APIMock;
use Valvoid\Fusion\Tests\Hub\Requests\Local\Archive\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Hub\Requests\Local\Archive\Mocks\CacheMock;
use Valvoid\Fusion\Tests\Hub\Requests\Local\Archive\Mocks\DirMock;
use Valvoid\Fusion\Tests\Hub\Requests\Local\Archive\Mocks\FileMock;
use Valvoid\Fusion\Tests\Test;
use Valvoid\Fusion\Wrappers\File;

class ArchiveTest extends Test
{
    protected string|array $coverage = [
        Archive::class,

        // ballast
        File::class
    ];

    protected Archive $archive;
    protected CacheMock $cacheMock;
    protected BoxMock $boxMock;
    protected APIMock $apiMock;
    protected array $source = [
        "api" => "test",
        "path" => "/path",
        "reference" => "1.0.0",
        "prefix" => ""
    ];

    public function __construct()
    {
        $this->cacheMock = new CacheMock;
        $this->apiMock = new APIMock;
        $this->boxMock = new BoxMock;
        $this->boxMock->dir = new DirMock;
        $this->boxMock->file = new FileMock;

        try {
            $this->archive = new Archive(2, $this->cacheMock,
                $this->source, $this->apiMock);

            $this->archive->addCacheId(1);

            $this->testInit();
            $this->testSuccess();
            $this->testErrorResponse();

        } catch (Throwable) {
            $this->handleFailedTest();
        }

        $this->boxMock::unsetInstance();
    }

    public function testInit(): void
    {
        // sync lock
        if ($this->cacheMock->lock !== 2)
            $this->handleFailedTest();

        // add and get cache IDs waiting for this sync
        $this->archive->addCacheId(5);

        if ($this->archive->getCacheIds() !== [1, 5])
            $this->handleFailedTest();
    }

    /**
     * @throws Request|Error
     */
    public function testSuccess(): void
    {
        $this->archive->execute();

        // all synchronized unlock
        if ($this->cacheMock->lock !== -1)
            $this->handleFailedTest();
    }

    public function testErrorResponse(): void
    {
        $this->apiMock->archive = "error message";

        try {
            $this->archive->execute();

        } catch(Request|Error) {
            return;
        }

        // no error drop
        $this->handleFailedTest();
    }
}