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

namespace Valvoid\Fusion\Tests\Units\Hub\Requests\Cache;

use Valvoid\Fusion\Hub\APIs\Remote\Remote;
use Valvoid\Fusion\Hub\Cache;
use Valvoid\Fusion\Hub\Requests\Cache\Error;
use Valvoid\Reflex\Test\Wrapper;
use Valvoid\Fusion\Log\Events\Errors\Request as RequestError;

class ErrorTest extends Wrapper
{
    public function testSynchronizationLock(): void
    {
        $cache = $this->createStub(Cache::class);
        $api = $this->createStub(Remote::class);
        $error = new Error(
            id: 1,
            cache: $cache,
            source: [],
            api: $api
        );

        // sync request before cache
        $error->addSyncId(5);

        $this->validate($error->hasSyncIds())
            ->as(true);

        $error->removeSyncId(5);
        $error->removeSyncId(1);

        $this->validate($error->hasSyncIds())
            ->as(false);
    }

    public function testUnknownApiError(): void
    {
        $cache = $this->createStub(Cache::class);
        $api = $this->createStub(Remote::class);
        $error = new Error(
            id: 1,
            cache: $cache,
            source: ["api" => "#0"],
            api: $api
        );

        $this->expectException(RequestError::class);

        $error->response(function () {});
    }
}