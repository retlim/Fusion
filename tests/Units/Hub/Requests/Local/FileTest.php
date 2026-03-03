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
use Valvoid\Fusion\Hub\Requests\Local\File;
use Valvoid\Reflex\Test\Wrapper;
use Valvoid\Fusion\Hub\Responses\Local\File as FileResponse;
use Valvoid\Fusion\Log\Events\Errors\Request as RequestError;
use Valvoid\Fusion\Wrappers\File as FileWrapper;

class FileTest extends Wrapper
{
    public function testCacheLock(): void
    {
        $box = $this->createStub(Box::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createStub(Local::class);
        $source =  [
            "api" => "#0",
            "path" => "#1",
            "reference" => "#2"
        ];

        $cache->fake("getLocalDir")
            ->expect(source: $source)
            ->return("#3")
            ->fake("lockFile")
            ->expect(source: $source, filename: "#5", id: 2);

        $file = new File(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            path: "#4",
            filename: "#5",
            api: $api
        );

        $file->addCacheId(4);
        $file->addCacheId(5);

        $this->validate($file->getCacheIds())
            ->as([4, 5]);
    }

    public function testSuccessfulExecution(): void
    {
        $box = $this->createMock(Box::class);
        $cache = $this->createMock(Cache::class);
        $api = $this->createMock(Local::class);
        $file = $this->createMock(FileWrapper::class);
        $response = $this->createMock(FileResponse::class);
        $source =  [
            "api" => "#0",
            "path" => "#1",
            "reference" => "#2",
            "prefix" => ""
        ];

        $cache->fake("getLocalDir")
            ->expect(source: $source)
            ->return("#3")
            ->fake("lockFile")
            ->expect(source: $source, filename: "#5", id: 2)
            ->fake("unlockFile")
            ->expect(source: $source, filename: "#5")
            ->fake("getLocalDir")
            ->expect(source: $source)
            ->return("#3")
            ->fake("isOffset")
            ->return(false);

        $api->fake("getFileContent")
            ->expect(path: "#1", reference: "#2", filename: "#4#5")
            ->return($response);

        $box->fake("get")
            ->expect(class: FileWrapper::class)
            ->return($file);

        $response->fake("getContent")
            ->return("###");

        $file->fake("put")
            ->expect(file: "#3#5", data: "###")
            ->return(true);

        $file = new File(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            path: "#4",
            filename: "#5",
            api: $api
        );

        $file->execute();
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

        $cache->fake("getLocalDir")
            ->expect(source: $source)
            ->return("#3")
            ->fake("lockFile")
            ->expect(source: $source, filename: "#5", id: 2)
            ->fake("getLocalDir")
            ->expect(source: $source)
            ->return("#3")
            ->fake("isOffset")
            ->return(false);

        $api->fake("getFileContent")
            ->expect(path: "#1", reference: "#2", filename: "#4#5")
            ->return("");

        $box->fake("get")
            ->expect(class: Dir::class)
            ->return($dir);

        $dir->fake("getRootDir")
            ->return("###");

        $archive = new File(
            box: $box,
            id: 2,
            cache: $cache,
            source: $source,
            path: "#4",
            filename: "#5",
            api: $api
        );

        $archive->addCacheId(11);
        $this->expectException(RequestError::class);
        $archive->execute();
    }
}