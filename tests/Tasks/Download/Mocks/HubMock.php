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
use Valvoid\Fusion\Hub\Logic;
use Valvoid\Fusion\Hub\Responses\Cache\Archive as ArchiveResponse;

/**
 * Mocked log.
 *
 * @Copyright Valvoid
 * @license GNU GPLv3
 */
class HubMock
{
    private ReflectionClass $reflection;

    /**
     * @throws ReflectionException
     */
    public function __construct()
    {
        $this->reflection = new ReflectionClass(Hub::class);
        $hub = $this->reflection->newInstanceWithoutConstructor();
        $this->reflection->setStaticPropertyValue("instance", $hub);
        $logic = $this->reflection->getProperty("logic");

        // pseudo logic
        $logic->setValue($hub, new class extends Logic
        {
            public function __construct() {}
            public function __destruct() {}

            public function addArchiveRequest(array $source): int
            {
                // fake request id
                return 1;
            }

            public function executeRequests(Closure $callback): void
            {
                $callback(new ArchiveResponse(1, __DIR__));
            }
        });
    }

    public function destroy(): void
    {
        $this->reflection->setStaticPropertyValue("instance", null);
    }
}