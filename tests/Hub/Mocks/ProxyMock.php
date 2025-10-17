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

namespace Valvoid\Fusion\Tests\Hub\Mocks;

use Closure;
use Valvoid\Fusion\Hub\Proxy;

class ProxyMock implements Proxy
{
    public $calls = [];

    public function addVersionsRequest(array $source): int
    {
        $this->calls[] = __FUNCTION__;

        return 0;
    }

    public function addMetadataRequest(array $source): int
    {
        $this->calls[] = __FUNCTION__;

        return 0;
    }

    public function addSnapshotRequest(array $source, string $path): int
    {
        $this->calls[] = __FUNCTION__;

        return 0;
    }

    public function addArchiveRequest(array $source): int
    {
        $this->calls[] = __FUNCTION__;

        return 0;
    }

    public function executeRequests(Closure $callback): void
    {
        $this->calls[] = __FUNCTION__;
    }
}