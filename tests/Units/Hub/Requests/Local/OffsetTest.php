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
use Valvoid\Fusion\Hub\APIs\Local\Offset as OffsetApi;
use Valvoid\Fusion\Hub\Cache;
use Valvoid\Fusion\Hub\Requests\Local\Offset;
use Valvoid\Fusion\Hub\Responses\Local\Offset as OffsetResponse;
use Valvoid\Fusion\Log\Events\Errors\Request as RequestError;
use Valvoid\Reflex\Test\Wrapper;

class OffsetTest extends Wrapper
{
    public function testCacheLock(): void
    {
        $box = $this->createStub(Box::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createStub(OffsetApi::class);
        $source =  [
            "api" => "#0",
            "path" => "#1",
            "reference" => "#2"
        ];

        $cache->fake("lockOffset")
            ->expect(source: $source, version: "#4", offset: "#5", id: 2)
            ->return(true);

        $offset = new Offset(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            api: $api,
            inline: "#4",
            inflated: ["offset" => "#5"],
        );

        $offset->addCacheId(4);
        $offset->addCacheId(5);
        $this->validate($offset->getCacheIds())
            ->as([4, 5]);
    }

    public function testSuccessfulExecution(): void
    {
        $box = $this->createMock(Box::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(OffsetApi::class);
        $response = $this->createMock(OffsetResponse::class);
        $source =  [
            "api" => "#0",
            "path" => "#1",
            "reference" => "#2",
            "prefix" => ""
        ];

        $cache->fake("lockOffset")
            ->expect(source: $source, version: "#4", offset: "#5", id: 2)
            ->return(true)
            ->fake("addOffset")
            ->expect(source: $source, inline: "#4",
                inflated: ["offset" => "#5"], id: "###")
            ->return(true);

        $api->fake("getOffset")
            ->expect(path: "#1", offset: "#5")
            ->return($response);

        $response->fake("getId")
            ->return("###");

        $offset = new Offset(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            api: $api,
            inline: "#4",
            inflated: ["offset" => "#5"],
        );

        $offset->execute();
    }

    public function testExecutionError(): void
    {
        $box = $this->createMock(Box::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(OffsetApi::class);
        $dir = $this->createMock(Dir::class);
        $source =  [
            "api" => "#0",
            "path" => "#1",
            "reference" => "#2",
            "prefix" => ""
        ];

        $cache->fake("lockOffset")
            ->expect(source: $source, version: "#4", offset: "#5", id: 2)
            ->return(true);

        $api->fake("getOffset")
            ->expect(path: "#1", offset: "#5")
            ->return("");

        $box->fake("get")
            ->expect(class: Dir::class)
            ->return($dir);

        $dir->fake("getRootDir")
            ->return("###");

        $offset = new Offset(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            api: $api,
            inline: "#4",
            inflated: ["offset" => "#5"],
        );

        $offset->addCacheId(11);
        $this->expectException(RequestError::class);
        $offset->execute();
    }
}