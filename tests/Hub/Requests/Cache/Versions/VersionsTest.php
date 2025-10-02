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

namespace Valvoid\Fusion\Tests\Hub\Requests\Cache\Versions;

use Throwable;
use Valvoid\Fusion\Hub\Requests\Cache\Versions;
use Valvoid\Fusion\Tests\Hub\Requests\Cache\Versions\Mocks\APIMock;
use Valvoid\Fusion\Tests\Hub\Requests\Cache\Versions\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Hub\Requests\Cache\Versions\Mocks\CacheMock;
use Valvoid\Fusion\Tests\Hub\Requests\Cache\Versions\Mocks\DirMock;
use Valvoid\Fusion\Tests\Test;
use Valvoid\Fusion\Hub\Responses\Cache\Versions as VersionsResponse;
use Valvoid\Fusion\Log\Events\Errors\Request;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class VersionsTest extends Test
{
    protected string|array $coverage = Versions::class;
    protected CacheMock $cacheMock;
    protected Versions $versions;
    protected APIMock $apiMock;
    protected array $source = [
        "api" => "test",
        "path" => "/path",
        "reference" => [],
        "prefix" => ""
    ];

    public function __construct()
    {
        $container = new BoxMock;
        $container->dir = new DirMock;
        $this->cacheMock = new CacheMock;
        $this->apiMock = new APIMock;
        $this->versions = new Versions(1, $this->cacheMock, $this->source,
            $this->apiMock, []);

        $this->testInit();
        $this->testResponse();
        $this->testErrorResponse();

        $container::unsetInstance();
    }

    public function testInit(): void
    {
        // sync request before cache
        $this->versions->addSyncId(5);

        if ($this->versions->hasSyncIds() === false)
            $this->handleFailedTest();

        $this->versions->removeSyncId(5);
        $this->versions->removeSyncId(1);

        if ($this->versions->hasSyncIds() !== false)
            $this->handleFailedTest();
    }

    public function testResponse(): void
    {
        try {
            $this->versions->response(function (VersionsResponse $response) {
                if ($response->getId() !== 1 ||
                    $response->getEntries() !== ["1.0.1", "1.0.0"])
                    $this->handleFailedTest();
            });

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }

    public function testErrorResponse(): void
    {
        $this->cacheMock->versions = [];

        try {
            $this->versions->response(function () {
                $this->handleFailedTest();
            });

        } catch (Request) {
            // The source reference does not match any version.
        }
    }
}