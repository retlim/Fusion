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
use Valvoid\Fusion\Hub\APIs\Remote\Offset as Remote;
use Valvoid\Fusion\Hub\APIs\Remote\Status;
use Valvoid\Fusion\Hub\Cache;
use Valvoid\Fusion\Hub\Requests\Remote\Lifecycle;
use Valvoid\Fusion\Hub\Requests\Remote\References;
use Valvoid\Fusion\Log\Events\Errors\Request;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Wrappers\Curl;
use Valvoid\Reflex\Test\Wrapper;
use Valvoid\Fusion\Log\Events\Errors\Request as RequestError;
use Valvoid\Fusion\Hub\Responses\Remote\References as ReferencesResponse;

class ReferencesTest extends Wrapper
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

        $api->fake("getReferencesUrl")
            ->expect(path: "#1")
            ->return("#4")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return(["#t0", "#t1"])
            ->fake("getAuthHeaderPrefix")
            ->return("#6")
            ->fake("getReferencesOptions")
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

        $cache->fake("lockReferences")
            ->expect(source: $source, id: 2)
            ->return(true);

        $references = new References(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            api: $api
        );

        $this->validate($references->getUrl())
            ->as("#4");

        $references->addCacheId(5);
        $references->addCacheId(1);

        $this->validate($references->getCacheIds())
            ->as([5, 1]);
    }

    public function testOkStatus(): void
    {
        $box = $this->createMock(Box::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(Remote::class);
        $curl = $this->createMock(Curl::class);
        $log = $this->createMock(Log::class);
        $request = $this->createMock(Request::class);
        $response = $this->createStub(ReferencesResponse::class);
        $source = [
            "api" => "#0",
            "path" => "#1",
            "reference" => "#2",
            "prefix" => "#3"
        ];

        $api->fake("getReferencesUrl")
            ->return("#4")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return([])
            ->fake("getAuthHeaderPrefix")
            ->return("#6")
            ->fake("getReferencesOptions")
            ->return([])
            ->fake("getStatus")
            ->expect(code: 200)
            ->repeat(1)
            ->return(Status::OK)
            ->fake("getReferences")
            ->expect(content: [0 => "###"])
            ->return($response)
            ->repeat(1);

        $response->fake("getEntries")
            ->return(["1.2.3"])
            ->repeat(1)
            ->fake("getUrl")
            ->return("#u")
            ->return("");

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
            ->return($request);

        $curl->fake("setOptions")
            ->return(true)
            ->repeat(1)
            ->fake("setOption")
            ->return(true)
            ->repeat(1)
            ->fake("getInfo")
            ->return(200)
            ->repeat(1)
            ->fake("reset")
            ->return(true);

        $cache->fake("lockReferences")
            ->expect(source: $source, id: 2)
            ->return(true)
            ->fake("unlockReferences")
            ->expect(source: $source)
            ->return(true);

        $log->fake("debug")
            ->expect(event: $request)
            ->repeat(1);

        $references = new References(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            api: $api
        );

        $references->addCacheId(1);
        $this->validate($references->getLifecycle(0, '{"0": "###"}'))
            ->as(Lifecycle::RELOAD);

        $this->validate($references->getLifecycle(0, '{"0": "###"}'))
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

        $api->fake("getReferencesUrl")
            ->return("")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return([])
            ->fake("getAuthHeaderPrefix")
            ->return("")
            ->fake("getReferencesOptions")
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

        $cache->fake("lockReferences")
            ->expect(source: $source, id: 2)
            ->return(true);

        $log->fake("debug")
            ->expect(event: $request);

        $references = new References(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            api: $api
        );

        $references->addCacheId(5);

        $this->expectException(RequestError::class);
        $references->getLifecycle(0, "###");
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

        $api->fake("getReferencesUrl")
            ->return("")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return(["#t0", "#t1"])
            ->fake("getAuthHeaderPrefix")
            ->return("")
            ->fake("getReferencesOptions")
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

        $cache->fake("lockReferences")
            ->expect(source: $source, id: 2)
            ->return(true);

        $log->fake("notice")
            ->expect(event: $request)
            ->fake("debug")
            ->expect(event: $request)
            ->repeat(1);

        $references = new References(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            api: $api
        );

        $references->addCacheId(1);
        $this->validate($references->getLifecycle( 0, "###"))
            ->as(Lifecycle::RELOAD);

        $this->expectException(RequestError::class);
        $references->getLifecycle( 0, "###");
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

        $api->fake("getReferencesUrl")
            ->return("")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return(["#t0", "#t1"])
            ->fake("getAuthHeaderPrefix")
            ->return("")
            ->fake("getReferencesOptions")
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

        $cache->fake("lockReferences")
            ->expect(source: $source, id: 2)
            ->return(true);

        $log->fake("notice")
            ->expect(event: $request)
            ->fake("debug")
            ->expect(event: $request)
            ->repeat(1);

        $references = new References(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            api: $api
        );

        $references->addCacheId(1);
        $this->validate($references->getLifecycle( 0, "###"))
            ->as(Lifecycle::RELOAD);

        $this->expectException(RequestError::class);
        $references->getLifecycle( 0, "###");
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

        $api->fake("getReferencesUrl")
            ->return("")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return(["#t0", "#t1"])
            ->fake("getAuthHeaderPrefix")
            ->return("")
            ->fake("getReferencesOptions")
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

        $cache->fake("lockReferences")
            ->expect(source: $source, id: 2)
            ->return(true);

        $log->fake("debug")
            ->expect(event: $request);

        $references = new References(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            api: $api
        );

        $references->addCacheId(1);
        $this->validate($references->getLifecycle(0, "###"))
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

        $api->fake("getReferencesUrl")
            ->return("")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return(["#t0", "#t1"])
            ->fake("getAuthHeaderPrefix")
            ->return("")
            ->fake("getReferencesOptions")
            ->return([]);

        $box->fake("get")
            ->expect(class: Curl::class)
            ->return($curl);

        $curl->fake("setOptions")
            ->return(true)
            ->repeat(1)
            ->fake("setOption")
            ->return(true);

        $cache->fake("lockReferences")
            ->expect(source: $source, id: 2)
            ->return(true);

        $references = new References(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            api: $api
        );

        $this->validate($references->getLifecycle(-1, "###"))
            ->as(Lifecycle::RELOAD);
    }
}