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

namespace Valvoid\Fusion\Tests\Hub\Requests\Cache\File;

use Throwable;
use Valvoid\Fusion\Hub\Requests\Cache\File;
use Valvoid\Fusion\Tests\Hub\Requests\Cache\File\Mocks\APIMock;
use Valvoid\Fusion\Tests\Hub\Requests\Cache\File\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Hub\Requests\Cache\File\Mocks\CacheMock;
use Valvoid\Fusion\Tests\Hub\Requests\Cache\File\Mocks\FileMock;
use Valvoid\Fusion\Tests\Test;
use Valvoid\Fusion\Hub\Responses\Cache\Metadata;
use Valvoid\Fusion\Hub\Responses\Cache\Snapshot;

class FileTest extends Test
{
    protected string|array $coverage = File::class;
    protected CacheMock $cacheMock;
    protected File $file;
    protected APIMock $apiMock;
    protected array $source = [
        "api" => "test",
        "path" => "/path",
        "reference" => "1.0.0",
        "prefix" => ""
    ];

    public function __construct()
    {
        $container = new BoxMock;
        $container->file = new FileMock;
        $this->cacheMock = new CacheMock;
        $this->apiMock = new APIMock;
        $this->file = new File(1, $this->cacheMock, $this->source,
            "", "/fusion.json", $this->apiMock);

        $this->testInit();
        $this->testMetadataResponse();
        $this->testSnapshotResponse();

        $container::unsetInstance();
    }

    public function testInit(): void
    {
        // sync request before cache
        $this->file->addSyncId(5);

        if ($this->file->hasSyncIds() === false)
            $this->handleFailedTest();

        $this->file->removeSyncId(5);
        $this->file->removeSyncId(1);

        if ($this->file->hasSyncIds() !== false)
            $this->handleFailedTest();
    }

    public function testMetadataResponse(): void
    {
        try {
            $this->file->response(function (Metadata $response) {
                if ($response->getId() !== 1 ||
                    $response->getFile() !== "/path/fusion.json" ||
                    $response->getContent() !== "###")
                    $this->handleFailedTest();
            });

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testSnapshotResponse(): void
    {
        $this->file = new File(1, $this->cacheMock, $this->source,
            "/nested", "/snapshot.json", $this->apiMock);

        try {
            $this->file->response(function (Snapshot $response) {
                if ($response->getId() !== 1 ||
                    $response->getFile() !== "/path/nested/snapshot.json" ||
                    $response->getContent() !== "###")
                    $this->handleFailedTest();
            });

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }
}