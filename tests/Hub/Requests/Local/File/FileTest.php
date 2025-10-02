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
 */

namespace Valvoid\Fusion\Tests\Hub\Requests\Local\File;

use Throwable;
use Valvoid\Fusion\Hub\Requests\Local\File;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Errors\Request;
use Valvoid\Fusion\Tests\Hub\Requests\Local\File\Mocks\APIMock;
use Valvoid\Fusion\Tests\Hub\Requests\Local\File\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Hub\Requests\Local\File\Mocks\CacheMock;
use Valvoid\Fusion\Tests\Hub\Requests\Local\File\Mocks\DirMock;
use Valvoid\Fusion\Tests\Hub\Requests\Local\File\Mocks\FileMock;
use Valvoid\Fusion\Tests\Test;
use Valvoid\Fusion\Wrappers\File as Wrapper;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class FileTest extends Test
{
    protected string|array $coverage = [
        File::class,

        // ballast
        Wrapper::class
    ];

    protected File $file;
    protected CacheMock $cacheMock;
    protected BoxMock $containerMock;
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
        $this->containerMock = new BoxMock;
        $this->containerMock->dir = new DirMock;
        $this->containerMock->file = new FileMock;

        try {
            $this->file = new File(2, $this->cacheMock,
                $this->source, "/nested", "/filename", $this->apiMock);

            $this->file->addCacheId(1);

            $this->testInit();
            $this->testSuccess();
            $this->testErrorResponse();

        } catch (Throwable) {
            $this->handleFailedTest();
        }

        $this->containerMock::unsetInstance();
    }

    public function testInit(): void
    {
        // sync lock
        if ($this->cacheMock->lock !== 2)
            $this->handleFailedTest();

        // add and get cache IDs waiting for this sync
        $this->file->addCacheId(5);

        if ($this->file->getCacheIds() !== [1, 5])
            $this->handleFailedTest();
    }

    /**
     * @throws Request|Error
     */
    public function testSuccess(): void
    {
        $this->file->execute();

        // all synchronized unlock
        if ($this->cacheMock->lock !== -1)
            $this->handleFailedTest();

        if ($this->containerMock->file->content !== "/filenamewhatever")
            $this->handleFailedTest();
    }

    public function testErrorResponse(): void
    {
        $this->apiMock->file = "error message";

        try {
            $this->file->execute();

        } catch(Request|Error) {
            return;
        }

        // no error drop
        $this->handleFailedTest();
    }
}