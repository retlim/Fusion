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

use Valvoid\Box\Box;
use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Hub\APIs\Local\Local as LocalApi;
use Valvoid\Fusion\Hub\APIs\Local\Offset as LocalOffsetApi;
use Valvoid\Fusion\Hub\APIs\Remote\Offset as RemoteOffsetApi;
use Valvoid\Fusion\Hub\APIs\Remote\Remote as RemoteApi;
use Valvoid\Fusion\Hub\Cache;
use Valvoid\Fusion\Hub\Requests\Cache\Versions;
use Valvoid\Reflex\Test\Wrapper;
use Valvoid\Fusion\Hub\Responses\Cache\Versions as VersionsResponse;
use Valvoid\Fusion\Log\Events\Errors\Request as RequestError;

class VersionsTest extends Wrapper
{
    public function testSynchronizationLock(): void
    {
        $box = $this->createStub(Box::class);
        $cache = $this->createStub(Cache::class);
        $api = $this->createStub(RemoteApi::class);
        $versions = new Versions(
            box: $box,
            id: 1,
            cache: $cache,
            source: [],
            api: $api,
            offsets: []
        );

        // sync request before cache
        $versions->addSyncId(5);

        $this->validate($versions->hasSyncIds())
            ->as(true);

        $versions->removeSyncId(5);
        $versions->removeSyncId(1);

        $this->validate($versions->hasSyncIds())
            ->as(false);
    }

    public function testRemoteApiResponse(): void
    {
        $box = $this->recycleStub(Box::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->recycleStub(RemoteApi::class);
        $source = [
            "api" => "#0",
            "path" => "#1",
            "reference" => ["#2"]
        ];

        $versions = new Versions(
            box: $box,
            id: 1,
            cache: $cache,
            source: $source,
            api: $api,
            offsets: []
        );

        $cache->fake("getVersions")
            ->expect(api: "#0",
                path: "#1",
                reference: ["#2"])
            ->return(["#3", "#4"]);

        $versions->response(function (VersionsResponse $versions) {
            $this->validate($versions->getId())
                ->as(1);

            $this->validate($versions->getEntries())
                ->as(["#3", "#4"]);

            $this->validate($versions->getTopEntry())
                ->as("#3");
        });
    }

    public function testRemoteApiError(): void
    {
        $box = $this->recycleStub(Box::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(RemoteApi::class);
        $source = [
            "api" => "#0",
            "path" => "#1",
            "reference" => ["#2"]
        ];

        $versions = new Versions(
            box: $box,
            id: 1,
            cache: $cache,
            source: $source,
            api: $api,
            offsets: []
        );

        $cache->fake("getVersions")
            ->expect(api: "#0",
                path: "#1",
                reference: ["#2"])
            ->return([]);

        $api->fake("getReferencesUrl")
            ->expect(path: "#1")
            ->return("#3");

        $this->expectException(RequestError::class);
        $versions->response(function () {});
    }

    public function testRemoteOffsetApiError(): void
    {
        $box = $this->recycleStub(Box::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(RemoteOffsetApi::class);
        $source = [
            "api" => "#0",
            "path" => "#1",
            "reference" => ["#2"]
        ];

        $versions = new Versions(
            box: $box,
            id: 1,
            cache: $cache,
            source: $source,
            api: $api,
            offsets: ["#4" => "#5"]
        );

        $cache->fake("getVersions")
            ->expect(api: "#0",
                path: "#1",
                reference: ["#2"])
            ->return([]);

        $api->fake("getReferencesUrl")
            ->expect(path: "#1")
            ->return("#3")
            ->fake("getOffsetUrl")
            ->expect(path: "#1", offset: "#4")
            ->return("#6");

        $this->expectException(RequestError::class);
        $versions->response(function () {});
    }

    public function testLocalApiError(): void
    {
        $box = $this->createMock(Box::class);
        $dir = $this->createMock(Dir::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createStub(LocalApi::class);
        $source = [
            "api" => "#0",
            "path" => "#1",
            "reference" => ["#2"]
        ];

        $versions = new Versions(
            box: $box,
            id: 1,
            cache: $cache,
            source: $source,
            api: $api,
            offsets: []
        );

        $box->fake("get")
            ->expect(class: Dir::class)
            ->return($dir);

        $dir->fake("getRootDir")
            ->return("###");

        $cache->fake("getVersions")
            ->expect(api: "#0",
                path: "#1",
                reference: ["#2"])
            ->return([]);

        $this->expectException(RequestError::class);
        $versions->response(function () {});
    }

    public function testLocalOffsetApiError(): void
    {
        $box = $this->createMock(Box::class);
        $dir = $this->createMock(Dir::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createStub(LocalOffsetApi::class);
        $source = [
            "api" => "#0",
            "path" => "#1",
            "reference" => ["#2"]
        ];

        $versions = new Versions(
            box: $box,
            id: 1,
            cache: $cache,
            source: $source,
            api: $api,
            offsets: ["#4" => "#5"]
        );

        $box->fake("get")
            ->expect(class: Dir::class)
            ->return($dir);

        $dir->fake("getRootDir")
            ->return("###");

        $cache->fake("getVersions")
            ->expect(api: "#0",
                path: "#1",
                reference: ["#2"])
            ->return([]);

        $this->expectException(RequestError::class);
        $versions->response(function () {});
    }
}