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
 * Hub.
 *
 * @Copyright Valvoid
 * @license GNU GPLv3
 */
interface Proxy
{
    /**
     * Enqueues versions request.
     *
     * @param array $source Source.
     * @return int Unique request ID.
     * @throws HubError Hub exception.
     * @throws RequestError Request exception.
     */
    public function addVersionsRequest(array $source): int;

    /**
     * Enqueues metadata file (fusion.json) request.
     *
     * @param array $source Source + pointer.
     * @return int Unique request ID.
     * @throws HubError Hub exception.
     */
    public function addMetadataRequest(array $source): int;

    /**
     * Enqueues snap file (snapshot.json) request.
     *
     * @param array $source Source + pointer.
     * @param string $path Relative to the package root cache path.
     * @return int Unique request ID.
     * @throws HubError Hub exception.
     */
    public function addSnapshotRequest(array $source, string $path): int;

    /**
     * Enqueues pointer request.
     *
     * @param array $source Source + pointer.
     * @return int Unique request ID.
     * @throws HubError Hub exception.
     */
    public function addArchiveRequest(array $source): int;

    /**
     * Loops request queue and passes individual request
     * results to the receiver.
     *
     * @param Closure $callback Response|result receiver.
     * @throws HubError Hub exception.
     */
    public function executeRequests(Closure $callback): void;
}