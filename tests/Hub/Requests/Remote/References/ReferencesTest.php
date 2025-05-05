<?php
/**
 * Fusion. A package manager for PHP-based projects.
 * Copyright Valvoid
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

namespace Valvoid\Fusion\Tests\Hub\Requests\Remote\References;

use Throwable;
use Valvoid\Fusion\Hub\Requests\Remote\Curl;
use Valvoid\Fusion\Hub\Requests\Remote\Lifecycle;
use Valvoid\Fusion\Hub\Requests\Remote\References;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Errors\Request;
use Valvoid\Fusion\Tests\Hub\Requests\Remote\References\Mocks\APIMock;
use Valvoid\Fusion\Tests\Hub\Requests\Remote\References\Mocks\CacheMock;
use Valvoid\Fusion\Tests\Hub\Requests\Remote\References\Mocks\ContainerMock;
use Valvoid\Fusion\Tests\Hub\Requests\Remote\References\Mocks\CurlMock;
use Valvoid\Fusion\Tests\Test;

/**
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ReferencesTest extends Test
{
    protected string|array $coverage = [
        References::class,

        // ballast
        Curl::class
    ];

    protected References $references;
    protected APIMock $apiMock;
    protected CurlMock $curlMock;
    protected CacheMock $cacheMock;

    protected array $source = [
        "api" => "test",
        "path" => "/path",
        "prefix" => ""

    ];

    public function __construct()
    {
        $this->curlMock = new CurlMock;
        $container = new ContainerMock($this->curlMock);
        $this->apiMock = new APIMock;
        $this->cacheMock = new CacheMock;

        try {

            // all requests are cache requests
            // if no cache for a package
            // pause/async cache request and
            // sync data before

            // sync request
            $this->references = new References(2,
                $this->cacheMock, $this->source, $this->apiMock);

            // async cache request
            // after sync done
            $this->references->addCacheId(1);

            $this->testInit();
            $this->testBadConnection();
            $this->testOkStatus();
            $this->testUnauthorizedStatus();
            $this->testToManyRequestsStatus();
            $this->testNotFoundStatus();
            $this->testForbiddenStatus();
            $this->testErrorStatus();
            $this->testOkStatusWithPrefix();

        } catch (Throwable) {
            $this->handleFailedTest();
        }

        $container->destroy();
    }

    public function testInit(): void
    {
         if ($this->references->getUrl() !== "api/path/references")
             $this->handleFailedTest();

        // sync lock
        if ($this->cacheMock->lock !== 2)
            $this->handleFailedTest();

        // add and get cache IDs waiting for this sync
        $this->references->addCacheId(5);

        if ($this->references->getCacheIds() !== [1, 5])
            $this->handleFailedTest();
    }

    public function testOkStatusWithPrefix(): void
    {
        // set source prefix
        $this->source["prefix"] = "v";
        $this->references = new References(2,
            $this->cacheMock, $this->source, $this->apiMock);

        $this->references->addCacheId(1);
        $this->curlMock->code = 200;
        $this->apiMock->prefix = "v";
        $this->apiMock->next = ["asdfsdf"];
        $this->cacheMock->versions = [];

        // has next
        if ($this->references->getLifecycle(

            // good connection code
            // json response with next page link
                0, "{}") !==

            // multi hub curl reload this request handle
            Lifecycle::RELOAD)
            $this->handleFailedTest();

        // last page
        if ($this->references->getLifecycle(

            // good connection code and
            // json response without next link
                0, "{}") !==

            // multi hub curl close this request handle
            Lifecycle::DONE)
            $this->handleFailedTest();

        // all versions passed to cache without prefix
        if ($this->cacheMock->versions !== ["4.5.6", "1.0.0", "3.4.5"])
            $this->handleFailedTest();
    }

    public function testOkStatus(): void
    {
        $this->curlMock->code = 200;

        // has next
        if ($this->references->getLifecycle(

                // good connection code
                // json response with next page link
                0, "{}") !==

            // multi hub curl reload this request handle
            Lifecycle::RELOAD)
            $this->handleFailedTest();

        // last page
        if ($this->references->getLifecycle(

                // good connection code and
                // json response without next link
                0, "{}") !==

            // multi hub curl close this request handle
            Lifecycle::DONE)
            $this->handleFailedTest();

        // all synchronized unlock
        if ($this->cacheMock->lock !== -1)
            $this->handleFailedTest();

        // all versions passed to cache
        if ($this->cacheMock->versions !== ["4.5.6", "1.0.0", "3.4.5"])
            $this->handleFailedTest();

        try {
            $this->references->getLifecycle(

                // good connection code and
                // invalid content - must be json
                0, "invalid");

        } catch(Request) {
            return;
        }

        // no error drop
        $this->handleFailedTest();
    }

    public function testErrorStatus(): void
    {
        // whatever unknown/error
        $this->curlMock->code = 894854;

        try {
            $this->references->getLifecycle(

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
        $this->references = new References(2,
            $this->cacheMock, $this->source, $this->apiMock);

        $this->references->addCacheId(1);
        $this->curlMock->code = 401;

        try {

            // token scope for other resource or
            // resource does not exist
            // drop error if no tokens left
            // test two tokens and
            // drop error
            for ($i = 1; $i < 3; ++$i)
                if ($this->references->getLifecycle(

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
        $this->references = new References(2,
            $this->cacheMock, $this->source, $this->apiMock);

        $this->references->addCacheId(1);
        $this->curlMock->code = 404;

        try {

            // token scope for other resource or
            // resource does not exist
            // drop error if no tokens left
            // test two tokens and
            // drop error
            for ($i = 1; $i < 3; ++$i)
                if ($this->references->getLifecycle(

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

    /**
     * @throws Error
     */
    public function testForbiddenStatus(): void
    {
        // reset request tokens
        $this->references = new References(2,
            $this->cacheMock, $this->source, $this->apiMock);

        $this->references->addCacheId(1);
        $this->curlMock->code = 403;

        try {

            // token scope for other resource or
            // resource does not exist
            // drop error if no tokens left
            // test two tokens and
            // drop error
            for ($i = 1; $i < 3; ++$i)
                if ($this->references->getLifecycle(

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

        if ($this->references->getLifecycle(

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
                if ($this->references->getLifecycle(

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