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

namespace Valvoid\Fusion\Tests\Tasks\Build\Mocks;

use Closure;
use Valvoid\Fusion\Hub\Proxy;

/**
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class HubMock implements Proxy
{
    public Closure $version;
    public Closure $metadata;
    public Closure $execute;

    public function addVersionsRequest(array $source): int
    {
        return call_user_func($this->version, $source);
    }

    public function addMetadataRequest(array $source): int
    {
       return call_user_func($this->metadata, $source);
    }

    public function executeRequests(Closure $callback): void
    {
        call_user_func($this->execute, $callback);
    }

    public function addSnapshotRequest(array $source, string $path): int {return 0;}
    public function addArchiveRequest(array $source): int{return 0;}
}