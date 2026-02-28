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

namespace Valvoid\Fusion\Tests\Units\Hub;

use Closure;
use Valvoid\Box\Box;
use Valvoid\Fusion\Config\Config;
use Valvoid\Fusion\Hub\APIs\Remote\Offset as RemoteOffsetApi;
use Valvoid\Fusion\Hub\APIs\Remote\Remote as RemoteApi;
use Valvoid\Fusion\Hub\APIs\Local\Local as LocalApi;
use Valvoid\Fusion\Hub\APIs\Local\Offset as LocalOffsetApi;
use Valvoid\Fusion\Hub\Cache;
use Valvoid\Fusion\Hub\Hub;
use Valvoid\Fusion\Hub\Parser;
use Valvoid\Fusion\Hub\Requests\Cache\Versions as CacheVersionsRequest;
use Valvoid\Fusion\Hub\Requests\Remote\Lifecycle;
use Valvoid\Fusion\Hub\Requests\Cache\Archive as CacheArchiveRequest;
use Valvoid\Fusion\Hub\Requests\Remote\Offset as RemoteOffsetRequest;
use Valvoid\Fusion\Hub\Requests\Remote\References as RemoteReferencesRequest;
use Valvoid\Fusion\Hub\Requests\Local\References as LocalReferencesRequest;
use Valvoid\Fusion\Hub\Requests\Local\Offset as LocalOffsetRequest;
use Valvoid\Fusion\Hub\Requests\Local\File as LocalFileRequest;
use Valvoid\Fusion\Hub\Requests\Local\Archive as LocalArchiveRequest;
use Valvoid\Fusion\Hub\Responses\Cache\Archive;
use Valvoid\Fusion\Hub\Responses\Cache\Metadata;
use Valvoid\Fusion\Hub\Responses\Cache\Versions as VersionsResponse;
use Valvoid\Fusion\Hub\Requests\Cache\File as CacheFileRequest;
use Valvoid\Fusion\Hub\Requests\Remote\File as RemoteFileRequest;
use Valvoid\Fusion\Hub\Requests\Remote\Archive as RemoteArchiveRequest;
use Valvoid\Fusion\Wrappers\Curl;
use Valvoid\Fusion\Wrappers\CurlMulti;
use Valvoid\Fusion\Wrappers\CurlShare;
use Valvoid\Reflex\Test\Wrapper;

class HubTest extends Wrapper
{
    public function testRemoteReferencesRequest(): void
    {
        $box = $this->createMock(Box::class);
        $curlMulti = $this->createStub(CurlMulti::class);
        $curlShare = $this->createStub(CurlShare::class);
        $config = $this->createMock(Config::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(RemoteApi::class);
        $parser = $this->createMock(Parser::class);
        $cacheRequest = $this->createMock(CacheVersionsRequest::class);
        $remoteRequest = $this->createMock(RemoteReferencesRequest::class);
        $curl = $this->createMock(Curl::class);
        $source = [
            "api" => "test",
            "reference" => "####"
        ];

        $config->fake("get")
            ->expect(breadcrumb: ["dir", "path"])
            ->return("#/#")
            ->expect(breadcrumb: ["hub", "apis"])
            ->return(["test" => ["api" => "#a0"]]);

        $box->fake("get")
            ->expect(class: Cache::class, arguments: ["root" => "#"])
            ->return($cache)
            ->expect(class: "#a0", arguments: ["config" => ["api" => "#a0"]])
            ->return($api)
            ->expect(class: Parser::class)
            ->return($parser)
            ->expect(class: CacheVersionsRequest::class,
                arguments: ["id" => 0 , "cache" => $cache,
                    "source" => $source, "api" => $api, "offsets" => []])
            ->return($cacheRequest)
            ->expect(class: RemoteReferencesRequest::class,
                arguments: ["id" => 1 , "cache" => $cache,
                    "source" => $source, "api" => $api])
            ->return($remoteRequest);

        $curlMulti->fake("setOption")
            ->return(true)
            ->fake("addCurl")
            ->return(0)
            ->fake("exec")
            ->return(0)
            ->repeat(1)
            ->fake("select")
            ->return(1)
            ->repeat(1)
            ->fake("getAllInfo")
            ->return(["handle" => "#11", "result" => "#22"])
            ->return(false)
            ->repeat(1)
            ->fake("getId")
            ->return(1)
            ->fake("getContent")
            ->return("#content")
            ->fake("removeCurl")
            ->return(0)
            ->fake("__destruct")
            ->return(true);

        $curlShare->fake("setOption")
            ->return(true)
            ->repeat(2);

        $parser->fake("getOffsets")
            ->return([]);

        $cache->fake("getReferencesState")
            ->return(false);

        $remoteRequest->fake("getCurl")
            ->return($curl)
            ->repeat(2)
            ->fake("addCacheId")
            ->expect(id: 0)
            ->fake("getLifecycle")
            ->return(Lifecycle::DONE)
            ->fake("getCacheIds")
            ->return([0]);

        $cacheRequest->fake("addSyncId")
            ->expect(id: 1)
            ->fake("hasSyncIds")
            ->return(true)
            ->return(false)
            ->fake("removeSyncId")
            ->expect(id: 1)
            ->fake("response")
            ->hook(function (Closure $callback) {
                $callback(new VersionsResponse(0, ["#v0"]));
            });

        $curl->fake("setShareOption")
            ->expect(curlShare: $curlShare)
            ->return(true);

        $api->fake("hasDelay")
            ->return(false)
            ->repeat(2);

        $hub = new Hub(
            box: $box,
            curlMulti: $curlMulti,
            curlShare: $curlShare,
            conf: $config
        );

        $id = $hub->addVersionsRequest($source);

        $hub->executeRequests(function (VersionsResponse $versions) use ($id) {
            $this->validate($versions->getId())
                ->as($id);

            $this->validate($versions->getEntries())
                ->as(["#v0"]);
        });
    }

    public function testRemoteOffsetRequest(): void
    {
        $box = $this->createMock(Box::class);
        $curlMulti = $this->createStub(CurlMulti::class);
        $curlShare = $this->recycleStub(CurlShare::class);
        $config = $this->recycleMock(Config::class);
        $cache = $this->recycleMock(Cache::class);
        $api = $this->createMock(RemoteOffsetApi::class);
        $parser = $this->createMock(Parser::class);
        $cacheRequest = $this->createMock(CacheVersionsRequest::class);
        $remoteOffsetRequest = $this->createMock(RemoteOffsetRequest::class);
        $remoteRequest = $this->recycleMock(RemoteReferencesRequest::class);
        $curl = $this->createMock(Curl::class);
        $source = [
            "api" => "test",
            "reference" => "####"
        ];

        $offsets = [[
            "version" => "#o0",
            "entry" => ["offset" => "#o1"]
        ]];

        $box->fake("get")
            ->expect(class: Cache::class, arguments: ["root" => "#"])
            ->return($cache)
            ->expect(class: "#a0", arguments: ["config" => ["api" => "#a0"]])
            ->return($api)
            ->expect(class: Parser::class)
            ->return($parser)
            ->expect(class: CacheVersionsRequest::class,
                arguments: ["id" => 0 , "cache" => $cache,
                    "source" => $source, "api" => $api, "offsets" => $offsets])
            ->return($cacheRequest)
            ->expect(class: RemoteOffsetRequest::class,
                arguments: ["id" => 1 , "cache" => $cache,
                    "source" => $source, "api" => $api,
                    "inline" => "#o0", "inflated" => ["offset" => "#o1"]])
            ->return($remoteOffsetRequest)
            ->expect(class: RemoteReferencesRequest::class,
                arguments: ["id" => 2, "cache" => $cache,
                    "source" => $source, "api" => $api])
            ->return($remoteRequest);

        $parser->fake("getOffsets")
            ->return($offsets);

        $curlMulti->fake("setOption")
            ->return(true)
            ->fake("addCurl")
            ->return(0)
            ->repeat(1)
            ->fake("exec")
            ->return(0)
            ->repeat(1)
            ->fake("select")
            ->return(1)
            ->repeat(1)
            ->fake("getAllInfo")
            ->return(["handle" => "#11", "result" => "#22"])
            ->return(["handle" => "#11", "result" => "#22"])
            ->return(false)
            ->repeat(1)
            ->fake("getId")
            ->return(1)
            ->return(2)
            ->fake("getContent")
            ->return("#content")
            ->repeat(1)
            ->fake("removeCurl")
            ->return(0)
            ->repeat(1)
            ->fake("__destruct")
            ->return(true);

        $remoteOffsetRequest->fake("getCurl")
            ->return($curl)
            ->repeat(2)
            ->fake("addCacheId")
            ->expect(id: 0)
            ->fake("getLifecycle")
            ->return(Lifecycle::DONE)
            ->fake("getCacheIds")
            ->return([0]);

        $cache->fake("getOffsetState")
            ->return(false);

        $cacheRequest->fake("addSyncId")
            ->expect(id: 1) // offset
            ->expect(id: 2)
            ->fake("hasSyncIds")
            ->return(true)
            ->return(false)
            ->fake("removeSyncId")
            ->expect(id: 1)
            ->expect(id: 2)
            ->fake("response")
            ->hook(function (Closure $callback) {
                $callback(new VersionsResponse(0, ["#v0"]));
            });

        $curl->fake("setShareOption")
            ->expect(curlShare: $curlShare)
            ->return(true);

        $hub = new Hub(
            box: $box,
            curlMulti: $curlMulti,
            curlShare: $curlShare,
            conf: $config
        );

        $api->fake("hasDelay")
            ->return(false)
            ->repeat(3);

        $id = $hub->addVersionsRequest($source);

        $hub->executeRequests(function (VersionsResponse $versions) use ($id) {
            $this->validate($versions->getId())
                ->as($id);

            $this->validate($versions->getEntries())
                ->as(["#v0"]);
        });
    }

    public function testRemoteMetadataRequest(): void
    {
        $box = $this->createMock(Box::class);
        $curlMulti = $this->createStub(CurlMulti::class);
        $curlShare = $this->createStub(CurlShare::class);
        $config = $this->createMock(Config::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(RemoteApi::class);
        $cacheRequest = $this->createMock(CacheFileRequest::class);
        $remoteRequest = $this->createMock(RemoteFileRequest::class);
        $curl = $this->createMock(Curl::class);
        $source = [
            "api" => "test",
            "reference" => "####"
        ];

        $config->fake("get")
            ->expect(breadcrumb: ["dir", "path"])
            ->return("#/#")
            ->expect(breadcrumb: ["hub", "apis"])
            ->return(["test" => ["api" => "#a0"]]);

        $box->fake("get")
            ->expect(class: Cache::class, arguments: ["root" => "#"])
            ->return($cache)
            ->expect(class: "#a0", arguments: ["config" => ["api" => "#a0"]])
            ->return($api)
            ->expect(class: CacheFileRequest::class,
                arguments: ["id" => 0 , "cache" => $cache,
                    "source" => $source, "path" => "",
                    "filename" => "/fusion.json", "api" => $api])
            ->return($cacheRequest)
            ->expect(class: RemoteFileRequest::class,
                arguments: ["id" => 1 , "cache" => $cache,
                    "source" => $source, "path" => "",
                    "filename" => "/fusion.json", "api" => $api])
            ->return($remoteRequest);

        $curlMulti->fake("setOption")
            ->return(true)
            ->fake("addCurl")
            ->return(0)
            ->fake("exec")
            ->return(0)
            ->repeat(1)
            ->fake("select")
            ->return(1)
            ->repeat(1)
            ->fake("getAllInfo")
            ->return(["handle" => "#11", "result" => "#22"])
            ->return(false)
            ->repeat(1)
            ->fake("getId")
            ->return(1)
            ->fake("getContent")
            ->return("#content")
            ->fake("removeCurl")
            ->return(0)
            ->fake("__destruct")
            ->return(true);

        $curl->fake("setShareOption")
            ->expect(curlShare: $curlShare)
            ->return(true);

        $curlShare->fake("setOption")
            ->return(true)
            ->repeat(2);

        $api->fake("hasDelay")
            ->return(false)
            ->repeat(2);

        $cache->fake("getFileState")
            ->expect(source: $source, filename: "/fusion.json", api: $api)
            ->return(false);

        $remoteRequest->fake("getCurl")
            ->return($curl)
            ->repeat(2)
            ->fake("addCacheId")
            ->expect(id: 0)
            ->fake("getLifecycle")
            ->return(Lifecycle::DONE)
            ->fake("getCacheIds")
            ->return([0]);

        $cacheRequest->fake("addSyncId")
            ->expect(id: 1)
            ->fake("hasSyncIds")
            ->return(true)
            ->return(false)
            ->fake("removeSyncId")
            ->expect(id: 1)
            ->fake("response")
            ->hook(function (Closure $callback) {
                $callback(new Metadata(0, "#0", "#1"));
            });

        $hub = new Hub(
            box: $box,
            curlMulti: $curlMulti,
            curlShare: $curlShare,
            conf: $config
        );

        $id = $hub->addMetadataRequest($source);
        $hub->executeRequests(function (Metadata $metadata) use ($id) {
            $this->validate($metadata->getId())
                ->as($id);

            $this->validate($metadata->getFile())
                ->as("#0");

            $this->validate($metadata->getContent())
                ->as("#1");
        });
    }

    public function testRemoteArchiveRequest(): void
    {
        $box = $this->createMock(Box::class);
        $curlMulti = $this->createStub(CurlMulti::class);
        $curlShare = $this->createStub(CurlShare::class);
        $config = $this->createMock(Config::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(RemoteApi::class);
        $cacheRequest = $this->createMock(CacheArchiveRequest::class);
        $remoteRequest = $this->createMock(RemoteArchiveRequest::class);
        $curl = $this->createMock(Curl::class);
        $source = [
            "api" => "test",
            "reference" => "####"
        ];

        $config->fake("get")
            ->expect(breadcrumb: ["dir", "path"])
            ->return("#/#")
            ->expect(breadcrumb: ["hub", "apis"])
            ->return(["test" => ["api" => "#a0"]]);

        $box->fake("get")
            ->expect(class: Cache::class, arguments: ["root" => "#"])
            ->return($cache)
            ->expect(class: "#a0", arguments: ["config" => ["api" => "#a0"]])
            ->return($api)
            ->expect(class: CacheArchiveRequest::class,
                arguments: ["id" => 0 , "cache" => $cache,
                    "source" => $source, "api" => $api])
            ->return($cacheRequest)
            ->expect(class: RemoteArchiveRequest::class,
                arguments: ["id" => 1 , "cache" => $cache,
                    "source" => $source, "api" => $api])
            ->return($remoteRequest);

        $curlMulti->fake("setOption")
            ->return(true)
            ->fake("addCurl")
            ->return(0)
            ->fake("exec")
            ->return(0)
            ->repeat(1)
            ->fake("select")
            ->return(1)
            ->repeat(1)
            ->fake("getAllInfo")
            ->return(["handle" => "#11", "result" => "#22"])
            ->return(false)
            ->repeat(1)
            ->fake("getId")
            ->return(1)
            ->fake("getContent")
            ->return("#content")
            ->fake("removeCurl")
            ->return(0)
            ->fake("__destruct")
            ->return(true);

        $curl->fake("setShareOption")
            ->expect(curlShare: $curlShare)
            ->return(true);

        $curlShare->fake("setOption")
            ->return(true)
            ->repeat(2);

        $api->fake("hasDelay")
            ->return(false)
            ->repeat(2);

        $cache->fake("getFileState")
            ->expect(source: $source, filename: "/archive.zip", api: $api)
            ->return(false);

        $remoteRequest->fake("getCurl")
            ->return($curl)
            ->repeat(2)
            ->fake("addCacheId")
            ->expect(id: 0)
            ->fake("getLifecycle")
            ->return(Lifecycle::DONE)
            ->fake("getCacheIds")
            ->return([0]);

        $cacheRequest->fake("addSyncId")
            ->expect(id: 1)
            ->fake("hasSyncIds")
            ->return(true)
            ->return(false)
            ->fake("removeSyncId")
            ->expect(id: 1)
            ->fake("response")
            ->hook(function (Closure $callback) {
                $callback(new Archive(0, "#0"));
            });

        $hub = new Hub(
            box: $box,
            curlMulti: $curlMulti,
            curlShare: $curlShare,
            conf: $config
        );

        $id = $hub->addArchiveRequest($source);
        $hub->executeRequests(function (Archive $archive) use ($id) {
            $this->validate($archive->getId())
                ->as($id);

            $this->validate($archive->getFile())
                ->as("#0/archive.zip");
        });
    }

    public function testLocalReferenceRequest(): void
    {
        $box = $this->createMock(Box::class);
        $curlMulti = $this->createStub(CurlMulti::class);
        $curlShare = $this->createStub(CurlShare::class);
        $config = $this->createMock(Config::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(LocalApi::class);
        $parser = $this->createMock(Parser::class);
        $cacheRequest = $this->createMock(CacheVersionsRequest::class);
        $localRequest = $this->createMock(LocalReferencesRequest::class);
        $source = [
            "api" => "test",
            "reference" => "####"
        ];

        $config->fake("get")
            ->expect(breadcrumb: ["dir", "path"])
            ->return("#/#")
            ->expect(breadcrumb: ["hub", "apis"])
            ->return(["test" => ["api" => "#a0"]]);

        $box->fake("get")
            ->expect(class: Cache::class, arguments: ["root" => "#"])
            ->return($cache)
            ->expect(class: "#a0", arguments: ["config" => ["api" => "#a0"]])
            ->return($api)
            ->expect(class: Parser::class)
            ->return($parser)
            ->expect(class: CacheVersionsRequest::class,
                arguments: ["id" => 0 , "cache" => $cache,
                    "source" => $source, "api" => $api, "offsets" => []])
            ->return($cacheRequest)
            ->expect(class: LocalReferencesRequest::class,
                arguments: ["id" => 1 , "cache" => $cache,
                    "source" => $source, "api" => $api])
            ->return($localRequest);

        $curlMulti->fake("setOption")
            ->return(true)
            ->fake("exec")
            ->return(0)
            ->repeat(1)
            ->fake("select")
            ->return(1)
            ->repeat(1)
            ->fake("getAllInfo")
            ->return(false)
            ->repeat(2)
            ->fake("__destruct")
            ->return(true);

        $curlShare->fake("setOption")
            ->return(true)
            ->repeat(2);

        $parser->fake("getOffsets")
            ->return([]);

        $cache->fake("getReferencesState")
            ->return(false);

        $localRequest->fake("execute")
            ->return(null)
            ->fake("addCacheId")
            ->expect(id: 0)
            ->fake("getCacheIds")
            ->return([0]);

        $cacheRequest->fake("addSyncId")
            ->expect(id: 1)
            ->fake("hasSyncIds")
            ->return(true)
            ->return(false)
            ->fake("removeSyncId")
            ->expect(id: 1)
            ->fake("response")
            ->hook(function (Closure $callback) {
                $callback(new VersionsResponse(0, ["#v0"]));
            });

        $hub = new Hub(
            box: $box,
            curlMulti: $curlMulti,
            curlShare: $curlShare,
            conf: $config
        );

        $id = $hub->addVersionsRequest($source);

        $hub->executeRequests(function (VersionsResponse $versions) use ($id) {
            $this->validate($versions->getId())
                ->as($id);

            $this->validate($versions->getEntries())
                ->as(["#v0"]);
        });
    }

    public function testLocalOffsetRequest(): void
    {
        $box = $this->createMock(Box::class);
        $curlMulti = $this->createStub(CurlMulti::class);
        $curlShare = $this->recycleStub(CurlShare::class);
        $config = $this->recycleMock(Config::class);
        $cache = $this->recycleMock(Cache::class);
        $api = $this->createMock(LocalOffsetApi::class);
        $parser = $this->createMock(Parser::class);
        $cacheRequest = $this->createMock(CacheVersionsRequest::class);
        $localOffsetRequest = $this->createMock(LocalOffsetRequest::class);
        $localRequest = $this->recycleMock(LocalReferencesRequest::class);
        $source = [
            "api" => "test",
            "reference" => "####"
        ];

        $offsets = [[
            "version" => "#o0",
            "entry" => ["offset" => "#o1"]
        ]];

        $box->fake("get")
            ->expect(class: Cache::class, arguments: ["root" => "#"])
            ->return($cache)
            ->expect(class: "#a0", arguments: ["config" => ["api" => "#a0"]])
            ->return($api)
            ->expect(class: Parser::class)
            ->return($parser)
            ->expect(class: CacheVersionsRequest::class,
                arguments: ["id" => 0 , "cache" => $cache,
                    "source" => $source, "api" => $api, "offsets" => $offsets])
            ->return($cacheRequest)
            ->expect(class: LocalOffsetRequest::class,
                arguments: ["id" => 1 , "cache" => $cache,
                    "source" => $source, "api" => $api,
                    "inline" => "#o0", "inflated" => ["offset" => "#o1"]])
            ->return($localOffsetRequest)
            ->expect(class: LocalReferencesRequest::class,
                arguments: ["id" => 2, "cache" => $cache,
                    "source" => $source, "api" => $api])
            ->return($localRequest);

        $parser->fake("getOffsets")
            ->return($offsets);

        $curlMulti->fake("setOption")
            ->return(true)
            ->fake("addCurl")
            ->return(0)
            ->repeat(1)
            ->fake("exec")
            ->return(0)
            ->repeat(1)
            ->fake("select")
            ->return(1)
            ->repeat(1)
            ->fake("getAllInfo")
            ->return(false)
            ->repeat(3)
            ->fake("__destruct")
            ->return(true);

        $localOffsetRequest->fake("execute")
            ->return(null)
            ->fake("addCacheId")
            ->expect(id: 0)
            ->fake("getCacheIds")
            ->return([0]);

        $cache->fake("getOffsetState")
            ->return(false);

        $cacheRequest->fake("addSyncId")
            ->expect(id: 1) // offset
            ->expect(id: 2)
            ->fake("hasSyncIds")
            ->return(true)
            ->return(false)
            ->fake("removeSyncId")
            ->expect(id: 1)
            ->expect(id: 2)
            ->fake("response")
            ->hook(function (Closure $callback) {
                $callback(new VersionsResponse(0, ["#v0"]));
            });

        $hub = new Hub(
            box: $box,
            curlMulti: $curlMulti,
            curlShare: $curlShare,
            conf: $config
        );

        $id = $hub->addVersionsRequest($source);

        $hub->executeRequests(function (VersionsResponse $versions) use ($id) {
            $this->validate($versions->getId())
                ->as($id);

            $this->validate($versions->getEntries())
                ->as(["#v0"]);
        });
    }

    public function testLocalMetadataRequest(): void
    {
        $box = $this->createMock(Box::class);
        $curlMulti = $this->createStub(CurlMulti::class);
        $curlShare = $this->createStub(CurlShare::class);
        $config = $this->createMock(Config::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(LocalApi::class);
        $cacheRequest = $this->createMock(CacheFileRequest::class);
        $localRequest = $this->createMock(LocalFileRequest::class);
        $source = [
            "api" => "test",
            "reference" => "####"
        ];

        $config->fake("get")
            ->expect(breadcrumb: ["dir", "path"])
            ->return("#/#")
            ->expect(breadcrumb: ["hub", "apis"])
            ->return(["test" => ["api" => "#a0"]]);

        $box->fake("get")
            ->expect(class: Cache::class, arguments: ["root" => "#"])
            ->return($cache)
            ->expect(class: "#a0", arguments: ["config" => ["api" => "#a0"]])
            ->return($api)
            ->expect(class: CacheFileRequest::class,
                arguments: ["id" => 0 , "cache" => $cache,
                    "source" => $source, "path" => "",
                    "filename" => "/fusion.json", "api" => $api])
            ->return($cacheRequest)
            ->expect(class: LocalFileRequest::class,
                arguments: ["id" => 1 , "cache" => $cache,
                    "source" => $source, "path" => "",
                    "filename" => "/fusion.json", "api" => $api])
            ->return($localRequest);

        $curlMulti->fake("setOption")
            ->return(true)
            ->fake("exec")
            ->return(0)
            ->repeat(1)
            ->fake("select")
            ->return(1)
            ->repeat(1)
            ->fake("getAllInfo")
            ->return(false)
            ->repeat(2)
            ->fake("__destruct")
            ->return(true);

        $curlShare->fake("setOption")
            ->return(true)
            ->repeat(2);

        $cache->fake("getFileState")
            ->expect(source: $source, filename: "/fusion.json", api: $api)
            ->return(false);

        $localRequest->fake("execute")
            ->return(null)
            ->fake("addCacheId")
            ->expect(id: 0)
            ->fake("getCacheIds")
            ->return([0]);

        $cacheRequest->fake("addSyncId")
            ->expect(id: 1)
            ->fake("hasSyncIds")
            ->return(true)
            ->return(false)
            ->fake("removeSyncId")
            ->expect(id: 1)
            ->fake("response")
            ->hook(function (Closure $callback) {
                $callback(new Metadata(0, "#0", "#1"));
            });

        $hub = new Hub(
            box: $box,
            curlMulti: $curlMulti,
            curlShare: $curlShare,
            conf: $config
        );

        $id = $hub->addMetadataRequest($source);
        $hub->executeRequests(function (Metadata $metadata) use ($id) {
            $this->validate($metadata->getId())
                ->as($id);

            $this->validate($metadata->getFile())
                ->as("#0");

            $this->validate($metadata->getContent())
                ->as("#1");
        });
    }

    public function testLocalArchiveRequest(): void
    {
        $box = $this->createMock(Box::class);
        $curlMulti = $this->createStub(CurlMulti::class);
        $curlShare = $this->createStub(CurlShare::class);
        $config = $this->createMock(Config::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(LocalApi::class);
        $cacheRequest = $this->createMock(CacheArchiveRequest::class);
        $localRequest = $this->createMock(LocalArchiveRequest::class);
        $source = [
            "api" => "test",
            "reference" => "####"
        ];

        $config->fake("get")
            ->expect(breadcrumb: ["dir", "path"])
            ->return("#/#")
            ->expect(breadcrumb: ["hub", "apis"])
            ->return(["test" => ["api" => "#a0"]]);

        $box->fake("get")
            ->expect(class: Cache::class, arguments: ["root" => "#"])
            ->return($cache)
            ->expect(class: "#a0", arguments: ["config" => ["api" => "#a0"]])
            ->return($api)
            ->expect(class: CacheArchiveRequest::class,
                arguments: ["id" => 0 , "cache" => $cache,
                    "source" => $source, "api" => $api])
            ->return($cacheRequest)
            ->expect(class: LocalArchiveRequest::class,
                arguments: ["id" => 1 , "cache" => $cache,
                    "source" => $source, "api" => $api])
            ->return($localRequest);

        $curlMulti->fake("setOption")
            ->return(true)
            ->fake("exec")
            ->return(0)
            ->repeat(1)
            ->fake("select")
            ->return(1)
            ->repeat(1)
            ->fake("getAllInfo")
            ->return(false)
            ->repeat(2)
            ->fake("__destruct")
            ->return(true);

        $curlShare->fake("setOption")
            ->return(true)
            ->repeat(2);

        $cache->fake("getFileState")
            ->expect(source: $source, filename: "/archive.zip", api: $api)
            ->return(false);

        $localRequest->fake("execute")
            ->return(null)
            ->fake("addCacheId")
            ->expect(id: 0)
            ->fake("getCacheIds")
            ->return([0]);

        $cacheRequest->fake("addSyncId")
            ->expect(id: 1)
            ->fake("hasSyncIds")
            ->return(true)
            ->return(false)
            ->fake("removeSyncId")
            ->expect(id: 1)
            ->fake("response")
            ->hook(function (Closure $callback) {
                $callback(new Archive(0, "#0"));
            });

        $hub = new Hub(
            box: $box,
            curlMulti: $curlMulti,
            curlShare: $curlShare,
            conf: $config
        );

        $id = $hub->addArchiveRequest($source);
        $hub->executeRequests(function (Archive $archive) use ($id) {
            $this->validate($archive->getId())
                ->as($id);

            $this->validate($archive->getFile())
                ->as("#0/archive.zip");
        });
    }
}