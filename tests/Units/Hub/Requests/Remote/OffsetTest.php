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
use Valvoid\Fusion\Hub\Requests\Remote\Offset;
use Valvoid\Fusion\Log\Events\Errors\Request;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Wrappers\Curl;
use Valvoid\Reflex\Test\Wrapper;
use Valvoid\Fusion\Log\Events\Errors\Request as RequestError;
use Valvoid\Fusion\Hub\Responses\Remote\Offset as OffsetResponse;

class OffsetTest extends Wrapper
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

        $api->fake("getOffsetUrl")
            ->expect(path: "#1", offset: "#9")
            ->return("#4")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return(["#t0", "#t1"])
            ->fake("getAuthHeaderPrefix")
            ->return("#6")
            ->fake("getOffsetOptions")
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

        $cache->fake("lockOffset")
            ->expect(source: $source, version: "#8", offset: "#9", id: 2)
            ->return(true);

        $offset = new Offset(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            api: $api,
            inline: "#8",
            inflated: ["offset" => "#9"]
        );

        $this->validate($offset->getUrl())
            ->as("#4");

        $offset->addCacheId(5);
        $offset->addCacheId(1);

        $this->validate($offset->getCacheIds())
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
        $response = $this->createStub(OffsetResponse::class);
        $source = [
            "api" => "#0",
            "path" => "#1",
            "reference" => "#2",
            "prefix" => "#3"
        ];

        $api->fake("getOffsetUrl")
            ->return("#4")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return([])
            ->fake("getAuthHeaderPrefix")
            ->return("#6")
            ->fake("getOffsetOptions")
            ->return([])
            ->fake("getStatus")
            ->expect(code: 200)
            ->return(Status::OK)
            ->fake("getOffset")
            ->expect(content: [0 => "###"])
            ->return($response);

        $response->fake("getId")
            ->return("#sha");

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
            ->return(200)
            ->fake("reset")
            ->return(true);

        $cache->fake("lockOffset")
            ->expect(source: $source, version: "#8", offset: "#9", id: 2)
            ->return(true)
            ->fake("addOffset")
            ->expect(source: $source, inline: "#8",
                inflated: ["offset" => "#9"], id: "#sha")
            ->return(true);

        $log->fake("debug")
            ->expect(event: $request);

        $offset = new Offset(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            api: $api,
            inline: "#8",
            inflated: ["offset" => "#9"]
        );

        $offset->addCacheId(1);
        $this->validate($offset->getLifecycle(0, '{"0": "###"}'))
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

        $api->fake("getOffsetUrl")
            ->return("")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return([])
            ->fake("getAuthHeaderPrefix")
            ->return("")
            ->fake("getOffsetOptions")
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

        $cache->fake("lockOffset")
            ->expect(source: $source, version: "#8", offset: "#9", id: 2)
            ->return(true);

        $log->fake("debug")
            ->expect(event: $request);

        $offset = new Offset(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            api: $api,
            inline: "#8",
            inflated: ["offset" => "#9"]
        );

        $offset->addCacheId(5);

        $this->expectException(RequestError::class);
        $offset->getLifecycle(0, "###");
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

        $api->fake("getOffsetUrl")
            ->return("")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return(["#t0", "#t1"])
            ->fake("getAuthHeaderPrefix")
            ->return("")
            ->fake("getOffsetOptions")
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

        $cache->fake("lockOffset")
            ->expect(source: $source, version: "#8", offset: "#9", id: 2)
            ->return(true);

        $log->fake("notice")
            ->expect(event: $request)
            ->fake("debug")
            ->expect(event: $request)
            ->repeat(1);

        $offset = new Offset(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            api: $api,
            inline: "#8",
            inflated: ["offset" => "#9"]
        );

        $offset->addCacheId(1);
        $this->validate($offset->getLifecycle( 0, "###"))
            ->as(Lifecycle::RELOAD);

        $this->expectException(RequestError::class);
        $offset->getLifecycle( 0, "###");
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

        $api->fake("getOffsetUrl")
            ->return("")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return(["#t0", "#t1"])
            ->fake("getAuthHeaderPrefix")
            ->return("")
            ->fake("getOffsetOptions")
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

        $cache->fake("lockOffset")
            ->expect(source: $source, version: "#8", offset: "#9", id: 2)
            ->return(true);

        $log->fake("notice")
            ->expect(event: $request)
            ->fake("debug")
            ->expect(event: $request)
            ->repeat(1);

        $offset = new Offset(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            api: $api,
            inline: "#8",
            inflated: ["offset" => "#9"]
        );

        $offset->addCacheId(1);
        $this->validate($offset->getLifecycle( 0, "###"))
            ->as(Lifecycle::RELOAD);

        $this->expectException(RequestError::class);
        $offset->getLifecycle( 0, "###");
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

        $api->fake("getOffsetUrl")
            ->return("")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return(["#t0", "#t1"])
            ->fake("getAuthHeaderPrefix")
            ->return("")
            ->fake("getOffsetOptions")
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

        $cache->fake("lockOffset")
            ->expect(source: $source, version: "#8", offset: "#9", id: 2)
            ->return(true);

        $log->fake("debug")
            ->expect(event: $request);

        $offset = new Offset(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            api: $api,
            inline: "#8",
            inflated: ["offset" => "#9"]
        );

        $offset->addCacheId(1);
        $this->validate($offset->getLifecycle(0, "###"))
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

        $api->fake("getOffsetUrl")
            ->return("")
            ->fake("getTokens")
            ->expect(path: "#1")
            ->return(["#t0", "#t1"])
            ->fake("getAuthHeaderPrefix")
            ->return("")
            ->fake("getOffsetOptions")
            ->return([]);

        $box->fake("get")
            ->expect(class: Curl::class)
            ->return($curl);

        $curl->fake("setOptions")
            ->return(true)
            ->repeat(1)
            ->fake("setOption")
            ->return(true);

        $cache->fake("lockOffset")
            ->expect(source: $source, version: "#8", offset: "#9", id: 2)
            ->return(true);

        $offset = new Offset(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            api: $api,
            inline: "#8",
            inflated: ["offset" => "#9"]
        );

        $this->validate($offset->getLifecycle(-1, "###"))
            ->as(Lifecycle::RELOAD);
    }
}