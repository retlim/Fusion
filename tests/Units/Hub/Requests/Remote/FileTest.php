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

namespace Valvoid\Fusion\Tests\Units\Hub\Requests\Remote;

use Valvoid\Box\Box;
use Valvoid\Fusion\Hub\APIs\Remote\Remote;
use Valvoid\Fusion\Hub\APIs\Remote\Status;
use Valvoid\Fusion\Hub\Cache;
use Valvoid\Fusion\Hub\Requests\Remote\File;
use Valvoid\Fusion\Hub\Requests\Remote\Lifecycle;
use Valvoid\Fusion\Log\Events\Errors\Request;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Wrappers\Curl;
use Valvoid\Reflex\Test\Wrapper;
use Valvoid\Fusion\Wrappers\File as FileWrapper;
use Valvoid\Fusion\Log\Events\Errors\Request as RequestError;

class FileTest extends Wrapper
{
    public function testCacheLock(): void
    {
        $box = $this->createMock(Box::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(Remote::class);
        $curl = $this->createMock(Curl::class);
        $source = [
            "api" => "#0",
            "path" => "#1",
            "reference" => "#2",
            "prefix" => "#3"
        ];

        $api->fake("getFileUrl")
            ->expect(path: "#1", reference: "#3#2", file: "#8#9")
            ->return("#4")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return(["#t0", "#t1"])
            ->fake("getAuthHeaderPrefix")
            ->return("#6")
            ->fake("getFileOptions")
            ->return(["#7" => "#77"]);

        $box->fake("get")
            ->expect(class: Curl::class)
            ->return($curl);

        $curl->fake("setOptions")
            ->return(true)
            ->repeat(1)
            ->fake("setOption")
            ->return(true)
            ->repeat(1);

        $cache->fake("isOffset")
            ->expect(source: $source)
            ->return(false)
            ->fake("getRemoteDir")
            ->expect(source: $source)
            ->return("#5")
            ->fake("lockFile")
            ->expect(source: $source, filename: "#9", id: 2);

        $file = new File(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            path: "#8",
            filename: "#9",
            api: $api
        );

        $this->validate($file->getUrl())
            ->as("#4");

        $file->addCacheId(5);
        $file->addCacheId(1);

        $this->validate($file->getCacheIds())
            ->as([5, 1]);
    }

    public function testOkStatus(): void
    {
        $box = $this->createMock(Box::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(Remote::class);
        $curl = $this->createMock(Curl::class);
        $fileWrapper = $this->createMock(FileWrapper::class);
        $log = $this->createMock(Log::class);
        $request = $this->createMock(Request::class);
        $source = [
            "api" => "#0",
            "path" => "#1",
            "reference" => "#2",
            "prefix" => "#3"
        ];

        $api->fake("getFileUrl")
            ->return("#4")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return([])
            ->fake("getAuthHeaderPrefix")
            ->return("#6")
            ->fake("getFileOptions")
            ->return([])
            ->fake("getStatus")
            ->expect(code: 200)
            ->return(Status::OK);

        $box->fake("get")
            ->expect(class: Curl::class)
            ->return($curl)
            ->expect(class: Log::class)
            ->return($log)
            ->expect(class: Request::class)
            ->return($request)
            ->expect(class: FileWrapper::class)
            ->return($fileWrapper);

        $curl->fake("setOptions")
            ->return(true)
            ->repeat(1)
            ->fake("setOption")
            ->return(true)
            ->fake("getInfo")
            ->return(200)
            ->fake("reset")
            ->return(true);

        $cache->fake("isOffset")
            ->expect(source: $source)
            ->return(false)
            ->fake("getRemoteDir")
            ->expect(source: $source)
            ->return("#5")
            ->fake("lockFile")
            ->expect(source: $source, filename: "#9", id: 2)
            ->fake("unlockFile")
            ->expect(source: $source, filename: "#9");

        $log->fake("debug")
            ->expect(event: $request);

        $fileWrapper->fake("put")
            ->expect(file: "#5#9", data: "###")
            ->return(true);

        $file = new File(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            path: "#8",
            filename: "#9",
            api: $api
        );

        $file->addCacheId(1);
        $this->validate($file->getLifecycle(0, "###"))
            ->as(Lifecycle::DONE);
    }

    public function testErrorStatus(): void
    {
        $box = $this->createMock(Box::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(Remote::class);
        $curl = $this->createMock(Curl::class);
        $log = $this->createMock(Log::class);
        $request = $this->createMock(Request::class);
        $source = [
            "api" => "#0",
            "path" => "#1",
            "reference" => "#2",
            "prefix" => "#3"
        ];

        $api->fake("getFileUrl")
            ->return("")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return([])
            ->fake("getAuthHeaderPrefix")
            ->return("")
            ->fake("getFileOptions")
            ->return([])
            ->fake("getStatus")
            ->expect(code: 200)
            ->return(Status::ERROR)
            ->fake("getErrorMessage")
            ->expect(content: "###")
            ->return("###");

        $box->fake("get")
            ->expect(class: Curl::class)
            ->return($curl)
            ->expect(class: Log::class)
            ->return($log)
            ->expect(class: Request::class)
            ->return($request);

        $curl->fake("setOptions")
            ->return(true)
            ->repeat(1)
            ->fake("setOption")
            ->return(true)
            ->fake("getInfo")
            ->return(200);

        $cache->fake("isOffset")
            ->expect(source: $source)
            ->return(false)
            ->fake("getRemoteDir")
            ->expect(source: $source)
            ->return("#5")
            ->fake("lockFile")
            ->expect(source: $source, filename: "#9", id: 2)
            ->fake("unlockFile")
            ->expect(source: $source, filename: "#9");

        $log->fake("debug")
            ->expect(event: $request);

        $file = new File(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            path: "#8",
            filename: "#9",
            api: $api
        );

        $file->addCacheId(5);

        $this->expectException(RequestError::class);
        $file->getLifecycle(0, "###");
    }

    public function testUnauthorizedStatus(): void
    {
        $box = $this->createMock(Box::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(Remote::class);
        $curl = $this->createMock(Curl::class);
        $log = $this->createMock(Log::class);
        $request = $this->createMock(Request::class);
        $source = [
            "api" => "#0",
            "path" => "#1",
            "reference" => "#2",
            "prefix" => "#3"
        ];

        $api->fake("getFileUrl")
            ->return("")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return(["#t0", "#t1"])
            ->fake("getAuthHeaderPrefix")
            ->return("")
            ->fake("getFileOptions")
            ->return([])
            ->fake("getStatus")
            ->expect(code: 401)
            ->repeat(1)
            ->return(Status::UNAUTHORIZED)
            ->fake("getErrorMessage")
            ->expect(content: "###")
            ->return("###")
            ->repeat(1)
            ->fake("addInvalidToken")
            ->expect(token: "#t0")
            ->return(true);

        $box->fake("get")
            ->expect(class: Curl::class)
            ->return($curl)
            ->expect(class: Log::class)
            ->return($log)
            ->expect(class: Request::class)
            ->return($request)
            ->expect(class: Log::class)
            ->return($log)
            ->expect(class: Request::class)
            ->return($request)
            ->expect(class: Log::class)
            ->return($log)
            ->expect(class: Request::class)
            ->return($request);

        $curl->fake("setOptions")
            ->return(true)
            ->repeat(1)
            ->fake("setOption")
            ->return(true)
            ->repeat(1)
            ->fake("getInfo")
            ->return(401)
            ->repeat(1);

        $cache->fake("isOffset")
            ->expect(source: $source)
            ->return(false)
            ->fake("getRemoteDir")
            ->expect(source: $source)
            ->return("#5")
            ->fake("lockFile")
            ->expect(source: $source, filename: "#9", id: 2)
            ->fake("unlockFile")
            ->expect(source: $source, filename: "#9");

        $log->fake("notice")
            ->expect(event: $request)
            ->fake("debug")
            ->expect(event: $request)
            ->repeat(1);

        $file = new File(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            path: "#8",
            filename: "#9",
            api: $api
        );

        $file->addCacheId(1);
        $this->validate($file->getLifecycle( 0, "###"))
            ->as(Lifecycle::RELOAD);

        $this->expectException(RequestError::class);
        $file->getLifecycle( 0, "###");
    }

    public function testNotFoundAndForbiddenStatus(): void
    {
        $box = $this->createMock(Box::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(Remote::class);
        $curl = $this->createMock(Curl::class);
        $log = $this->createMock(Log::class);
        $request = $this->createMock(Request::class);
        $source = [
            "api" => "#0",
            "path" => "#1",
            "reference" => "#2",
            "prefix" => "#3"
        ];

        $api->fake("getFileUrl")
            ->return("")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return(["#t0", "#t1"])
            ->fake("getAuthHeaderPrefix")
            ->return("")
            ->fake("getFileOptions")
            ->return([])
            ->fake("getStatus")
            ->expect(code: 404)
            ->repeat(1)
            ->return(Status::NOT_FOUND)
            ->fake("getErrorMessage")
            ->expect(content: "###")
            ->return("###")
            ->repeat(1);

        $box->fake("get")
            ->expect(class: Curl::class)
            ->return($curl)
            ->expect(class: Log::class)
            ->return($log)
            ->expect(class: Request::class)
            ->return($request)
            ->expect(class: Log::class)
            ->return($log)
            ->expect(class: Request::class)
            ->return($request)
            ->expect(class: Log::class)
            ->return($log)
            ->expect(class: Request::class)
            ->return($request);

        $curl->fake("setOptions")
            ->return(true)
            ->repeat(1)
            ->fake("setOption")
            ->expect(option: CURLOPT_HTTPHEADER, value: ["#t0"])
            ->return(true)
            ->expect(option: CURLOPT_HTTPHEADER, value: ["#t1"])
            ->repeat(1)
            ->fake("getInfo")
            ->return(404)
            ->repeat(1);

        $cache->fake("isOffset")
            ->expect(source: $source)
            ->return(false)
            ->fake("getRemoteDir")
            ->expect(source: $source)
            ->return("#5")
            ->fake("lockFile")
            ->expect(source: $source, filename: "#9", id: 2)
            ->fake("unlockFile")
            ->expect(source: $source, filename: "#9");

        $log->fake("notice")
            ->expect(event: $request)
            ->fake("debug")
            ->expect(event: $request)
            ->repeat(1);

        $file = new File(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            path: "#8",
            filename: "#9",
            api: $api
        );

        $file->addCacheId(1);
        $this->validate($file->getLifecycle( 0, "###"))
            ->as(Lifecycle::RELOAD);

        $this->expectException(RequestError::class);
        $file->getLifecycle( 0, "###");
    }

    public function testToManyRequestsStatus(): void
    {
        $box = $this->createMock(Box::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(Remote::class);
        $curl = $this->createMock(Curl::class);
        $log = $this->createMock(Log::class);
        $request = $this->createMock(Request::class);
        $source = [
            "api" => "#0",
            "path" => "#1",
            "reference" => "#2",
            "prefix" => "#3"
        ];

        $api->fake("getFileUrl")
            ->return("")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return(["#t0", "#t1"])
            ->fake("getAuthHeaderPrefix")
            ->return("")
            ->fake("getFileOptions")
            ->return([])
            ->fake("getStatus")
            ->expect(code: 429)
            ->return(Status::TO_MANY_REQUESTS)
            ->fake("getRateLimitReset")
            ->return(11)
            ->fake("setDelay")
            ->expect(timestamp: 11, id: 2);

        $box->fake("get")
            ->expect(class: Curl::class)
            ->return($curl)
            ->expect(class: Log::class)
            ->return($log)
            ->expect(class: Request::class)
            ->return($request);

        $curl->fake("setOptions")
            ->return(true)
            ->repeat(1)
            ->fake("setOption")
            ->return(true)
            ->fake("getInfo")
            ->return(429);

        $cache->fake("isOffset")
            ->expect(source: $source)
            ->return(false)
            ->fake("getRemoteDir")
            ->expect(source: $source)
            ->return("#5")
            ->fake("lockFile")
            ->expect(source: $source, filename: "#9", id: 2);

        $log->fake("debug")
            ->expect(event: $request);

        $file = new File(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            path: "#8",
            filename: "#9",
            api: $api
        );

        $file->addCacheId(1);
        $this->validate($file->getLifecycle(0, "###"))
            ->as(Lifecycle::DELAY);
    }

    public function testBadConnection(): void
    {
        $box = $this->createMock(Box::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(Remote::class);
        $curl = $this->createMock(Curl::class);
        $source = [
            "api" => "#0",
            "path" => "#1",
            "reference" => "#2",
            "prefix" => "#3"
        ];

        $api->fake("getFileUrl")
            ->return("")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return(["#t0", "#t1"])
            ->fake("getAuthHeaderPrefix")
            ->return("")
            ->fake("getFileOptions")
            ->return([]);

        $box->fake("get")
            ->expect(class: Curl::class)
            ->return($curl);

        $curl->fake("setOptions")
            ->return(true)
            ->repeat(1)
            ->fake("setOption")
            ->return(true);

        $cache->fake("isOffset")
            ->expect(source: $source)
            ->return(false)
            ->fake("getRemoteDir")
            ->expect(source: $source)
            ->return("#5")
            ->fake("lockFile")
            ->expect(source: $source, filename: "#9", id: 2);

        $file = new File(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            path: "#8",
            filename: "#9",
            api: $api
        );

        $this->validate($file->getLifecycle(-1, "###"))
            ->as(Lifecycle::RELOAD);
    }
}