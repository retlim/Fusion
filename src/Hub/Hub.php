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

namespace Valvoid\Fusion\Hub;

use Closure;
use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Hub\Proxy\Proxy;
use Valvoid\Fusion\Log\Events\Errors\Error as HubError;
use Valvoid\Fusion\Log\Events\Errors\Request as RequestError;

/**
 * Static hub proxy.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Hub
{
    /**
     * Enqueues versions request.
     *
     * @param array $source Source.
     * @return int Unique request ID.
     * @throws HubError Hub exception.
     * @throws RequestError Request exception.
     */
    public static function addVersionsRequest(array $source): int
    {
        return Box::getInstance()->get(Proxy::class)
            ->addVersionsRequest($source);
    }

    /**
     * Enqueues metadata file (fusion.json) request.
     *
     * @param array $source Source + pointer.
     * @return int Unique request ID.
     * @throws HubError Hub exception.
     */
    public static function addMetadataRequest(array $source): int
    {
        return Box::getInstance()->get(Proxy::class)
            ->addMetadataRequest($source);
    }

    /**
     * Enqueues snap file (snapshot.json) request.
     *
     * @param array $source Source + pointer.
     * @param string $path Relative to the package root cache path.
     * @return int Unique request ID.
     * @throws HubError Hub exception.
     */
    public static function addSnapshotRequest(array $source, string $path): int
    {
        return Box::getInstance()->get(Proxy::class)
            ->addSnapshotRequest($source, $path);
    }

    /**
     * Enqueues pointer request.
     *
     * @param array $source Source + pointer.
     * @return int Unique request ID.
     * @throws HubError Hub exception.
     */
    public static function addArchiveRequest(array $source): int
    {
        return Box::getInstance()->get(Proxy::class)
            ->addArchiveRequest($source);
    }

    /**
     * Loops request queue and passes individual request
     * results to the receiver.
     *
     * @param Closure $callback Response|result receiver.
     * @throws HubError Hub exception.
     */
    public static function executeRequests(Closure $callback): void
    {
        Box::getInstance()->get(Proxy::class)
            ->executeRequests($callback);
    }
}