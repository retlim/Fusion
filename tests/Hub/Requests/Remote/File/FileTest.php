<?php
/**
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
 */

namespace Valvoid\Fusion\Tests\Hub\Requests\Remote\File;

use Throwable;
use Valvoid\Fusion\Hub\Requests\Remote\File;
use Valvoid\Fusion\Hub\Requests\Remote\Lifecycle;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Errors\Request;
use Valvoid\Fusion\Tests\Hub\Requests\Remote\File\Mocks\APIMock;
use Valvoid\Fusion\Tests\Hub\Requests\Remote\File\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Hub\Requests\Remote\File\Mocks\CacheMock;
use Valvoid\Fusion\Tests\Hub\Requests\Remote\File\Mocks\CurlMock;
use Valvoid\Fusion\Tests\Hub\Requests\Remote\File\Mocks\FileMock;
use Valvoid\Fusion\Tests\Hub\Requests\Remote\File\Mocks\LogMock;
use Valvoid\Fusion\Tests\Test;
use Valvoid\Fusion\Wrappers\File as Wrapper;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class FileTest extends Test
{
    protected string|array $coverage = [
        File::class,

        // ballast
        Wrapper::class
    ];

    protected File $file;
    protected APIMock $apiMock;
    protected CurlMock $curlMock;
    protected CacheMock $cacheMock;
    protected array $source = [
        "api" => "test",
        "path" => "/path",
        "reference" => "1.0.0",
        "prefix" => ""
    ];

    public function __construct()
    {
        $this->curlMock = new CurlMock;
        $container = new BoxMock;
        $container->curl = $this->curlMock;
        $container->log = new LogMock;
        $container->file = new FileMock;
        $this->apiMock = new APIMock;
        $this->cacheMock = new CacheMock;

        try {

            // all requests are cache requests
            // if no cache for a package
            // pause/async cache request and
            // sync data before

            // sync request
            $this->file = new File(2, $this->cacheMock, $this->source,
                "/nested", "/filename", $this->apiMock);

            // async cache request
            // after sync done
            $this->file->addCacheId(1);

            $this->testInit();
            $this->testBadConnection();
            $this->testOkStatus();
            $this->testUnauthorizedStatus();
            $this->testToManyRequestsStatus();
            $this->testNotFoundStatus();
            $this->testForbiddenStatus();
            $this->testErrorStatus();

        } catch (Throwable) {
            $this->handleFailedTest();
        }

        $container::unsetInstance();
    }

    public function testInit(): void
    {
        if ($this->file->getUrl() !== "api/path/nested/filename/1.0.0")
            $this->handleFailedTest();

        // sync lock
        if ($this->cacheMock->lock !== 2)
            $this->handleFailedTest();

        // add and get cache IDs waiting for this sync
        $this->file->addCacheId(5);

        if ($this->file->getCacheIds() !== [1, 5])
            $this->handleFailedTest();
    }

    public function testOkStatus(): void
    {
        $this->curlMock->code = 200;

        if ($this->file->getLifecycle(

                // good connection code and
                // metadata or snapshot json response
                0, "{}") !==

            // multi hub curl close this request handle
            Lifecycle::DONE)
            $this->handleFailedTest();

        // all synchronized unlock
        if ($this->cacheMock->lock !== -1)
            $this->handleFailedTest();
    }

    public function testErrorStatus(): void
    {
        // whatever unknown/error
        $this->curlMock->code = 894854;

        try {
            $this->file->getLifecycle(

                // good connection code
                0, "");

        } catch(Request) {
            return;
        }

        // no error drop
        $this->handleFailedTest();
    }

    /**
     * @throws Error
     */
    public function testUnauthorizedStatus(): void
    {
        // reset request tokens
        $this->file = new File(2, $this->cacheMock, $this->source,
            "/path", "filename", $this->apiMock);

        $this->file->addCacheId(1);
        $this->curlMock->code = 401;

        try {

            // token scope for other resource or
            // resource does not exist
            // drop error if no tokens left
            // test two tokens and
            // drop error
            for ($i = 1; $i < 3; ++$i)
                if ($this->file->getLifecycle(

                    // good connection code
                        0, "") !==

                    // multi hub curl reload this request handle
                    Lifecycle::RELOAD || $this->curlMock->optionValue !==

                    // +1 already exchanged token
                    ["Authorization: Bearer $i"] ||

                    // -1 prev token
                    $this->apiMock->invalidToken != ($i - 1))
                    $this->handleFailedTest();

        } catch(Request) {
            return;
        }

        // no error drop
        $this->handleFailedTest();
    }

    /**
     * @throws Error
     */
    public function testNotFoundStatus(): void
    {
        // reset request tokens
        $this->file = new File(2, $this->cacheMock, $this->source,
            "/path", "filename", $this->apiMock);

        $this->file->addCacheId(1);
        $this->curlMock->code = 404;

        try {

            // token scope for other resource or
            // resource does not exist
            // drop error if no tokens left
            // test two tokens and
            // drop error
            for ($i = 1; $i < 3; ++$i)
                if ($this->file->getLifecycle(

                    // good connection code
                        0, "") !==

                    // multi hub curl reload this request handle
                    Lifecycle::RELOAD || $this->curlMock->optionValue !==

                    // +1 already exchanged token
                    ["Authorization: Bearer $i"])
                    $this->handleFailedTest();

        } catch(Request) {
            return;
        }

        // no error drop
        $this->handleFailedTest();
    }


    public function testForbiddenStatus(): void
    {
        // reset request tokens
        $this->file = new File(2, $this->cacheMock, $this->source,
            "/path", "filename", $this->apiMock);

        $this->file->addCacheId(1);
        $this->curlMock->code = 403;

        try {

            // token scope for other resource or
            // resource does not exist
            // drop error if no tokens left
            // test two tokens and
            // drop error
            for ($i = 1; $i < 3; ++$i)
                if ($this->file->getLifecycle(

                    // good connection code
                        0, "") !==

                    // multi hub curl reload this request handle
                    Lifecycle::RELOAD || $this->curlMock->optionValue !==

                    // +1 already exchanged token
                    ["Authorization: Bearer $i"])
                    $this->handleFailedTest();

        } catch(Request) {
            return;
        }

        // no error drop
        $this->handleFailedTest();
    }

    public function testToManyRequestsStatus(): void
    {
        $this->curlMock->code = 429;

        if ($this->file->getLifecycle(

            // good connection code
                0, "") !==

            // multi hub curl pause this request handle and
            // reload after timestamp
            Lifecycle::DELAY || !$this->apiMock->hasDelay())
            $this->handleFailedTest();

        $this->apiMock->resetDelay();
    }

    public function testBadConnection(): void
    {
        try {

            // retry up to 10 times and
            // drop request error
            for ($i = 0; $i < 10; ++$i)
                if ($this->file->getLifecycle(

                    // something was not ok code
                        -1, "") !==

                    // multi hub curl reload this request handle
                    Lifecycle::RELOAD)
                    $this->handleFailedTest();

        } catch (Request) {
            return;
        }

        // no error drop
        $this->handleFailedTest();
    }
}