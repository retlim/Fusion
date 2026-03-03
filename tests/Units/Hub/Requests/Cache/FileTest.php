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

namespace Valvoid\Fusion\Tests\Units\Hub\Requests\Cache;

use Valvoid\Fusion\Hub\APIs\Local\Local;
use Valvoid\Fusion\Hub\APIs\Remote\Remote;
use Valvoid\Fusion\Hub\Cache;
use Valvoid\Fusion\Hub\Requests\Cache\File;
use Valvoid\Fusion\Hub\Responses\Cache\Metadata;
use Valvoid\Fusion\Hub\Responses\Cache\Snapshot;
use Valvoid\Reflex\Test\Wrapper;
use Valvoid\Fusion\Wrappers\File as FileWrapper;

class FileTest extends Wrapper
{
    public function testSynchronizationLock(): void
    {
        $cache = $this->createStub(Cache::class);
        $api = $this->createStub(Local::class);
        $fileWrapper = $this->createStub(FileWrapper::class);
        $file = new File(
            id: 1,
            cache: $cache,
            source: [],
            path: "",
            filename: "",
            api: $api,
            fileWrapper: $fileWrapper
        );

        // sync request before cache
        $file->addSyncId(5);

        $this->validate($file->hasSyncIds())
            ->as(true);

        $file->removeSyncId(5);
        $file->removeSyncId(1);

        $this->validate($file->hasSyncIds())
            ->as(false);
    }

    public function testRemoteApiMetadataResponse(): void
    {
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(Remote::class);
        $fileWrapper = $this->createMock(FileWrapper::class);
        $source = [
            "api" => "test",
            "path" => "#0",
            "reference" => "#1",
            "prefix" => "#3"
        ];

        $fileWrapper->fake("get")
            ->return("#4");

        $api->fake("getFileUrl")
            ->return("#6");

        $file = new File(
            id: 1,
            cache: $cache,
            source: $source,
            path: "",
            filename: "/fusion.json",
            api: $api,
            fileWrapper: $fileWrapper
        );

        $cache->fake("getRemoteDir")
            ->expect(source: $source)
            ->return("#5");

        $file->response(function (Metadata $metadata) {
            $this->validate($metadata->getId())
                ->as(1);

            $this->validate($metadata->getFile())
                ->as("#6");

            $this->validate($metadata->getContent())
                ->as("#4");
        });
    }


    public function testLocalApiMetadataResponse(): void
    {
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(Local::class);
        $fileWrapper = $this->createMock(FileWrapper::class);
        $source = [
            "api" => "test",
            "path" => "#0",
            "reference" => "#1",
            "prefix" => "#3"
        ];

        $fileWrapper->fake("get")
            ->return("#4");

        $api->fake("getFileLocation")
            ->return("#6");

        $file = new File(
            id: 1,
            cache: $cache,
            source: $source,
            path: "",
            filename: "/fusion.json",
            api: $api,
            fileWrapper: $fileWrapper
        );

        $cache->fake("getLocalDir")
            ->expect(source: $source)
            ->return("#5");

        $file->response(function (Metadata $metadata) {
            $this->validate($metadata->getId())
                ->as(1);

            $this->validate($metadata->getFile())
                ->as("#6");

            $this->validate($metadata->getContent())
                ->as("#4");
        });
    }

    public function testRemoteApiSnapshotResponse(): void
    {
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(Remote::class);
        $fileWrapper = $this->createMock(FileWrapper::class);
        $source = [
            "api" => "test",
            "path" => "#0",
            "reference" => "#1",
            "prefix" => "#3"
        ];

        $fileWrapper->fake("get")
            ->return("#4");

        $api->fake("getFileUrl")
            ->return("#6");

        $file = new File(
            id: 1,
            cache: $cache,
            source: $source,
            path: "",
            filename: "/snapshot.json",
            api: $api,
            fileWrapper: $fileWrapper
        );

        $cache->fake("getRemoteDir")
            ->expect(source: $source)
            ->return("#5");

        $file->response(function (Snapshot $snapshot) {
            $this->validate($snapshot->getId())
                ->as(1);

            $this->validate($snapshot->getFile())
                ->as("#6");

            $this->validate($snapshot->getContent())
                ->as("#4");
        });
    }

    public function testLocalApiSnapshotResponse(): void
    {
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(Local::class);
        $fileWrapper = $this->createMock(FileWrapper::class);
        $source = [
            "api" => "test",
            "path" => "#0",
            "reference" => "#1",
            "prefix" => "#3"
        ];

        $fileWrapper->fake("get")
            ->return("#4");

        $api->fake("getFileLocation")
            ->return("#6");

        $file = new File(
            id: 1,
            cache: $cache,
            source: $source,
            path: "",
            filename: "/snapshot.json",
            api: $api,
            fileWrapper: $fileWrapper
        );

        $cache->fake("getLocalDir")
            ->expect(source: $source)
            ->return("#5");

        $file->response(function (Snapshot $snapshot) {
            $this->validate($snapshot->getId())
                ->as(1);

            $this->validate($snapshot->getFile())
                ->as("#6");

            $this->validate($snapshot->getContent())
                ->as("#4");
        });
    }
}