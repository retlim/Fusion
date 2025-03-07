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

namespace Valvoid\Fusion\Tests\Tasks\Build\Mocks;

use Closure;
use ReflectionClass;
use Valvoid\Fusion\Hub\Hub;
use Valvoid\Fusion\Hub\Proxy\Proxy;
use Valvoid\Fusion\Hub\Responses\Cache\Metadata;
use Valvoid\Fusion\Hub\Responses\Cache\Versions;

/**
 * Mocked hub proxy.
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
    }

    public function setUpNestedMetadataImplication(): void
    {
        // same as
        $this->setUpExternalRootSourceImplication();
    }

    public function setUpRecursiveMetadataImplication(): void
    {
        // same as
        $this->setUpExternalRootSourceImplication();
    }

    public function setUpExternalRootSourceImplication(): void
    {
        $this->reflection->setStaticPropertyValue("instance", new class extends Hub
        {
            public function __construct()
            {
                $this->proxy = new class implements Proxy
                {
                    private int $counter = 0;
                    private array $versionRequests = [];
                    private array $metaRequest = [];

                    public function addVersionsRequest(array $source): int
                    {
                        $this->versionRequests[$this->counter] = $source;

                        return $this->counter++;
                    }

                    public function addMetadataRequest(array $source): int
                    {
                        $this->metaRequest[$this->counter] = $source;

                        return $this->counter++;
                    }

                    public function executeRequests(Closure $callback): void
                    {
                        while ($this->versionRequests || $this->metaRequest) {
                            foreach ($this->versionRequests as $id => $versionRequest) {
                                unset($this->versionRequests[$id]);

                                if ($versionRequest[0] == "metadata3")
                                    $callback(new Versions($id, ["2.30.1", "2.0.0:offset", "1.0.0"]));

                                else
                                    $callback(new Versions($id, ["1.0.0"]));
                            }

                            foreach ($this->metaRequest as $id => $metaRequest) {
                                unset($this->metaRequest[$id]);

                                $callback(new Metadata($id, "", json_encode($metaRequest)));
                            }
                        }
                    }

                    public function addSnapshotRequest(array $source, string $path): int { return 0; }
                    public function addArchiveRequest(array $source): int { return 0; }
                };
            }
        });
    }

    public function destroy(): void
    {
        $this->reflection->setStaticPropertyValue("instance", null);
    }
}