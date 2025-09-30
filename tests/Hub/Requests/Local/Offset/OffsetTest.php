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

namespace Valvoid\Fusion\Tests\Hub\Requests\Local\Offset;

use Throwable;
use Valvoid\Fusion\Hub\Requests\Local\Offset;
use Valvoid\Fusion\Log\Events\Errors\Request;
use Valvoid\Fusion\Tests\Hub\Requests\Local\Offset\Mocks\APIMock;
use Valvoid\Fusion\Tests\Hub\Requests\Local\Offset\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Hub\Requests\Local\Offset\Mocks\CacheMock;
use Valvoid\Fusion\Tests\Hub\Requests\Local\Offset\Mocks\DirMock;
use Valvoid\Fusion\Tests\Test;

/**
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class OffsetTest extends Test
{
    protected string|array $coverage = Offset::class;

    protected Offset $offset;
    protected APIMock $apiMock;
    protected CacheMock $cacheMock;
    protected array $source = [
        "api" => "test",
        "path" => "/path",
        "prefix" => ""
    ];

    protected array $data = [
        "version" => "1.0.0",
        "entry" => [
            "major" => "1",
            "minor" => "0",
            "patch" => "0",
            "release" => "",
            "build" => "",
            "offset" => "main",
            "sign" => "=="
        ]];

    public function __construct()
    {
        $this->apiMock = new APIMock;
        $this->cacheMock = new CacheMock;
        $container = new BoxMock;
        $container->dir = new DirMock;

        try {
            $this->offset = new Offset(2, $this->cacheMock, $this->source,
                $this->apiMock, $this->data["version"], $this->data["entry"]);

            $this->offset->addCacheId(1);

            $this->testInit();
            $this->testSuccess();
            $this->testConflict();
            $this->testErrorResponse();

        } catch (Throwable) {
            $this->handleFailedTest();
        }

        $container::unsetInstance();
    }


    public function testConflict(): void
    {
        $this->cacheMock->conflict = true;

        try {
            $this->offset->execute();

        } catch(Request) {
            return;
        }

        // no error drop
        $this->handleFailedTest();
    }

    /**
     * @throws Request
     */
    public function testSuccess(): void
    {
        $this->offset->execute();

        // all synchronized unlock
        if ($this->cacheMock->lock !== "main")
            $this->handleFailedTest();

        // all versions passed to cache
        if ($this->cacheMock->offset !== "1.0.0")
            $this->handleFailedTest();
    }

    public function testErrorResponse(): void
    {
        $this->cacheMock->conflict = false;
        $this->apiMock->offset = "error message";

        try {
            $this->offset->execute();

        } catch(Request) {
            return;
        }

        // no error drop
        $this->handleFailedTest();
    }

    public function testInit(): void
    {
        // sync lock
        if ($this->cacheMock->lock !== "main")
            $this->handleFailedTest();

        // add and get cache IDs waiting for this sync
        $this->offset->addCacheId(5);

        if ($this->offset->getCacheIds() !== [1, 5])
            $this->handleFailedTest();
    }
}