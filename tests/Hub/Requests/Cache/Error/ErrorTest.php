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

namespace Valvoid\Fusion\Tests\Hub\Requests\Cache\Error;

use Throwable;
use Valvoid\Fusion\Hub\Requests\Cache\Error;
use Valvoid\Fusion\Tests\Hub\Requests\Cache\Error\Mocks\APIMock;
use Valvoid\Fusion\Tests\Hub\Requests\Cache\Error\Mocks\CacheMock;
use Valvoid\Fusion\Tests\Test;

class ErrorTest extends Test
{
    protected string|array $coverage = Error::class;
    protected CacheMock $cacheMock;
    protected Error $error;
    protected APIMock $apiMock;
    protected array $source = [
        "api" => "test"
    ];

    public function __construct()
    {
        $this->cacheMock = new CacheMock;
        $this->apiMock = new APIMock;
        $this->error = new Error(1, $this->cacheMock,
            $this->source, $this->apiMock);

        $this->testInit();
        $this->testUnknownApiError();
    }

    public function testInit(): void
    {
        // sync request before cache
        $this->error->addSyncId(5);

        if ($this->error->hasSyncIds() === false)
            $this->handleFailedTest();

        $this->error->removeSyncId(5);
        $this->error->removeSyncId(1);

        if ($this->error->hasSyncIds() !== false)
            $this->handleFailedTest();
    }

    public function testUnknownApiError(): void
    {
        try {
            $this->error->response(function () {
                $this->handleFailedTest();
            });

        // unknown api error drop
        } catch (Throwable) {
            return;
        }

        $this->handleFailedTest();
    }
}