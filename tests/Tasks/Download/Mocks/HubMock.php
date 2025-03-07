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

namespace Valvoid\Fusion\Tests\Tasks\Download\Mocks;

use Closure;
use ReflectionClass;
use ReflectionException;
use Valvoid\Fusion\Hub\Hub;
use Valvoid\Fusion\Hub\Proxy\Proxy;
use Valvoid\Fusion\Hub\Responses\Cache\Archive as ArchiveResponse;

/**
 * Mocked log.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class HubMock
{
    private ReflectionClass $reflection;

    public function __construct()
    {
        $this->reflection = new ReflectionClass(Hub::class);
        $this->reflection->setStaticPropertyValue("instance", new class extends Hub
        {
            public function __construct()
            {
                $this->proxy = new class implements Proxy {

                    public function addVersionsRequest(array $source): int
                    {
                        return 0;
                    }

                    public function addMetadataRequest(array $source): int
                    {
                        return 0;
                    }

                    public function addSnapshotRequest(array $source, string $path): int
                    {
                        return 0;
                    }

                    public function addArchiveRequest(array $source): int
                    {
                        // fake request id
                        return 1;
                    }

                    public function executeRequests(Closure $callback): void
                    {
                        $callback(new ArchiveResponse(1, __DIR__));
                    }
                };
            }
        });
    }

    public function destroy(): void
    {
        $this->reflection->setStaticPropertyValue("instance", null);
    }
}