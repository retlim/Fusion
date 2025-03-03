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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Valvoid\Fusion\Hub\Proxy;

use Closure;
use Valvoid\Fusion\Log\Events\Errors\Error as HubError;
use Valvoid\Fusion\Log\Events\Errors\Request as RequestError;

/**
 * Default hub proxy instance.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Instance implements Proxy
{
    /** @var Proxy Implementation. */
    protected Proxy $logic;

    /**
     * Constructs the hub.
     *
     *  @param Proxy|Logic $logic Any or default logic implementation.
     */
    public function __construct(Proxy|Logic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * Enqueues versions request.
     *
     * @param array $source Source.
     * @return int Unique request ID.
     * @throws HubError Hub exception.
     * @throws RequestError Request exception.
     */
    public function addVersionsRequest(array $source): int
    {
        return $this->logic->addVersionsRequest($source);
    }

    /**
     * Enqueues metadata file (fusion.json) request.
     *
     * @param array $source Source + pointer.
     * @return int Unique request ID.
     * @throws HubError Hub exception.
     */
    public function addMetadataRequest(array $source): int
    {
        return $this->logic->addMetadataRequest($source);
    }

    /**
     * Enqueues snap file (snapshot.json) request.
     *
     * @param array $source Source + pointer.
     * @param string $path Relative to the package root cache path.
     * @return int Unique request ID.
     * @throws HubError Hub exception.
     */
    public function addSnapshotRequest(array $source, string $path): int
    {
        return $this->logic->addSnapshotRequest($source, $path);
    }

    /**
     * Enqueues pointer request.
     *
     * @param array $source Source + pointer.
     * @return int Unique request ID.
     * @throws HubError Hub exception.
     */
    public function addArchiveRequest(array $source): int
    {
        return $this->logic->addArchiveRequest($source);
    }

    /**
     * Loops request queue and passes individual request
     * results to the receiver.
     *
     * @param Closure $callback Response|result receiver.
     * @throws HubError Hub exception.
     */
    public function executeRequests(Closure $callback): void
    {
        $this->logic->executeRequests($callback);
    }
}