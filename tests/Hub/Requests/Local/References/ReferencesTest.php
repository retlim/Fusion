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

namespace Valvoid\Fusion\Tests\Hub\Requests\Local\References;

use Throwable;
use Valvoid\Fusion\Hub\Requests\Local\References;
use Valvoid\Fusion\Log\Events\Errors\Request;
use Valvoid\Fusion\Tests\Hub\Requests\Local\References\Mocks\APIMock;
use Valvoid\Fusion\Tests\Hub\Requests\Local\References\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Hub\Requests\Local\References\Mocks\CacheMock;
use Valvoid\Fusion\Tests\Hub\Requests\Local\References\Mocks\DirMock;
use Valvoid\Fusion\Tests\Test;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class ReferencesTest extends Test
{
    protected string|array $coverage = References::class;

    protected References $references;
    protected CacheMock $cacheMock;
    protected APIMock $apiMock;
    protected array $source = [
        "api" => "test",
        "path" => "/path",
        "prefix" => ""
    ];

    public function __construct()
    {
        $this->cacheMock = new CacheMock;
        $this->apiMock = new APIMock;
        $container = new BoxMock;
        $container->dir = new DirMock;

        try {
            $this->references = new References(2, $this->cacheMock,
                $this->source, $this->apiMock);

            $this->references->addCacheId(1);

            $this->testInit();
            $this->testSuccess();
            $this->testConflict();
            $this->testErrorResponse();

        } catch (Throwable) {
            $this->handleFailedTest();
        }

        $container::unsetInstance();
    }

    public function testInit(): void
    {
        // sync lock
        if ($this->cacheMock->lock !== 2)
            $this->handleFailedTest();

        // add and get cache IDs waiting for this sync
        $this->references->addCacheId(5);

        if ($this->references->getCacheIds() !== [1, 5])
            $this->handleFailedTest();
    }

    /**
     * @throws Request
     */
    public function testSuccess(): void
    {
        $this->references->execute();

        // all synchronized unlock
        if ($this->cacheMock->lock !== -1)
            $this->handleFailedTest();

        // all versions passed to cache
        if ($this->cacheMock->versions !== ["4.5.6", "1.0.0", "3.4.5"])
            $this->handleFailedTest();
    }

    public function testErrorResponse(): void
    {
        $this->cacheMock->conflict = false;
        $this->apiMock->references = "error message";

        try {
            $this->references->execute();

        } catch(Request) {
            return;
        }

        // no error drop
        $this->handleFailedTest();
    }

    public function testConflict(): void
    {
        $this->cacheMock->conflict = true;

        try {
            $this->references->execute();

        } catch(Request) {
            return;
        }

        // no error drop
        $this->handleFailedTest();
    }
}