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

namespace Valvoid\Fusion\Tests\Units\Hub\Requests\Local;

use Valvoid\Box\Box;
use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Hub\APIs\Local\Local;
use Valvoid\Fusion\Hub\Cache;
use Valvoid\Fusion\Hub\Requests\Local\References;
use Valvoid\Reflex\Test\Wrapper;
use Valvoid\Fusion\Hub\Responses\Local\References as ReferencesResponse;
use Valvoid\Fusion\Log\Events\Errors\Request as RequestError;

class ReferencesTest extends Wrapper
{
    public function testCacheLock(): void
    {
        $box = $this->createStub(Box::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createStub(Local::class);
        $source =  [
            "api" => "#0",
            "path" => "#1",
            "reference" => "#2",
            "prefix" => ""
        ];

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

        $references->addCacheId(4);
        $references->addCacheId(5);
        $this->validate($references->getCacheIds())
            ->as([4, 5]);
    }

    public function testSuccessfulExecution(): void
    {
        $box = $this->createMock(Box::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(Local::class);
        $response = $this->createMock(ReferencesResponse::class);
        $source =  [
            "api" => "#0",
            "path" => "#1",
            "reference" => "#2",
            "prefix" => ""
        ];

        $cache->fake("lockReferences")
            ->expect(source: $source, id: 2)
            ->fake("unlockReferences")
            ->expect(source: $source)
            ->fake("addVersion")
            ->expect(api: "#0", path: "#1", inline: "1.2.3")
            ->return(true);

        $api->fake("getReferences")
            ->expect(path: "#1")
            ->return($response);

        $response->fake("getEntries")
            ->return(["1.2.3"]);

        $references = new References(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            api: $api
        );

        $references->execute();
    }

    public function testExecutionError(): void
    {
        $box = $this->createMock(Box::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(Local::class);
        $dir = $this->createMock(Dir::class);
        $source =  [
            "api" => "#0",
            "path" => "#1",
            "reference" => "#2",
            "prefix" => ""
        ];

        $cache->fake("lockReferences")
            ->expect(source: $source, id: 2);

        $api->fake("getReferences")
            ->expect(path: "#1")
            ->return("");

        $box->fake("get")
            ->expect(class: Dir::class)
            ->return($dir);

        $dir->fake("getRootDir")
            ->return("###");

        $references = new References(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            api: $api
        );

        $references->addCacheId(11);
        $this->expectException(RequestError::class);
        $references->execute();
    }
}