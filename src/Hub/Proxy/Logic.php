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
use CurlMultiHandle;
use CurlShareHandle;
use Valvoid\Fusion\Config\Config;
use Valvoid\Fusion\Hub\APIs\Local\Local as LocalApi;
use Valvoid\Fusion\Hub\APIs\Local\Offset as LocalOffsetApi;
use Valvoid\Fusion\Hub\APIs\Remote\Offset as RemoteOffsetApi;
use Valvoid\Fusion\Hub\APIs\Remote\Remote as RemoteApi;
use Valvoid\Fusion\Hub\Cache;
use Valvoid\Fusion\Hub\Parser;
use Valvoid\Fusion\Hub\Requests\Cache\Archive as CacheArchiveRequest;
use Valvoid\Fusion\Hub\Requests\Cache\Cache as CacheRequest;
use Valvoid\Fusion\Hub\Requests\Cache\Error as CacheErrorRequest;
use Valvoid\Fusion\Hub\Requests\Cache\File as CacheFileRequest;
use Valvoid\Fusion\Hub\Requests\Cache\Versions as CacheVersionsRequest;
use Valvoid\Fusion\Hub\Requests\Local\Archive as LocalArchiveRequest;
use Valvoid\Fusion\Hub\Requests\Local\File as LocalFileRequest;
use Valvoid\Fusion\Hub\Requests\Local\Local as LocalRequest;
use Valvoid\Fusion\Hub\Requests\Local\Offset as LocalOffsetRequest;
use Valvoid\Fusion\Hub\Requests\Local\References as LocalReferencesRequest;
use Valvoid\Fusion\Hub\Requests\Remote\Archive as RemoteArchiveRequest;
use Valvoid\Fusion\Hub\Requests\Remote\File as RemoteFileRequest;
use Valvoid\Fusion\Hub\Requests\Remote\Lifecycle;
use Valvoid\Fusion\Hub\Requests\Remote\Offset as RemoteOffsetRequest;
use Valvoid\Fusion\Hub\Requests\Remote\References as RemoteReferencesRequest;
use Valvoid\Fusion\Hub\Requests\Remote\Remote as RemoteRequest;
use Valvoid\Fusion\Log\Events\Errors\Error as HubError;
use Valvoid\Fusion\Log\Events\Errors\Request as RequestError;
use Valvoid\Fusion\Log\Log;

/**
 * Default hub implementation.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Logic implements Proxy
{
    /** @var CurlMultiHandle Curl handles wrapper. */
    protected CurlMultiHandle $handle;

    /** @var CurlShareHandle Share handle. */
    protected CurlShareHandle $shareHandle;

    /** @var array<string, RemoteApi|LocalApi> APIs. */
    protected array $apis;

    /** @var Cache Cache. */
    protected Cache $cache;

    /** @var int Unique request ID. */
    protected int $id = 0;

    /** @var array{
     *     cache: array<int, CacheRequest>,
     *     local: array<int, LocalRequest>,
     *     remote: array<int, RemoteRequest>
     * } Request queues. */
    protected array $queues = [
        "cache" => [],
        "local" => [],
        "remote" => []
    ];

    /**
     * Constructs the logic.
     *
     * @throws HubError Hub error.
     */
    public function __construct()
    {
        $config = Config::get("hub");
        $this->shareHandle = curl_share_init();
        $this->handle = curl_multi_init();

        // local API root
        $root = Config::get("dir", "path");
        $root = dirname($root);
        $this->cache = new Cache($root);

        foreach ($config["apis"] as $id => $api)
            $this->apis[$id] = (is_subclass_of($api["api"], LocalApi::class)) ?
                new $api["api"]($root, $api) :
                new $api["api"]($api);

        // recycle data
        curl_share_setopt($this->shareHandle, CURLSHOPT_SHARE,
            CURL_LOCK_DATA_SSL_SESSION);
        curl_share_setopt($this->shareHandle, CURLSHOPT_SHARE,
            CURL_LOCK_DATA_DNS);
        curl_share_setopt($this->shareHandle, CURLSHOPT_SHARE,
            CURL_LOCK_DATA_COOKIE);
        curl_multi_setopt($this->handle, CURLMOPT_PIPELINING,

            // recycle connections
            CURLPIPE_MULTIPLEX);
    }

    /** Destructs the logic. */
    public function __destruct()
    {
        curl_share_close($this->shareHandle);
        curl_multi_close($this->handle);
    }

    /**
     * Adds error request.
     *
     * @param array $source Source.
     * @return int Request ID.
     */
    protected function addErrorRequest(array $source): int
    {
        $request = new CacheErrorRequest($this->id, $this->cache, $source, null);
        $this->queues["cache"][$this->id] = $request;

        return $this->id++;
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
        $api = $this->apis[$source["api"]] ?? null;

        // enqueue lazy drop
        // return ID for error path first
        // support detailed exceptions
        if ($api === null)
            return $this->addErrorRequest($source);

        // visible external request
        // hub caches everything
        $id = $this->id++;
        $offsets = Parser::getOffsets($source["reference"]);
        $request = new CacheVersionsRequest($id, $this->cache, $source, $api, $offsets);
        $this->queues["cache"][$id] = $request;

        // sync/instant local
        if ($api instanceof LocalApi) {
            if ($api instanceof LocalOffsetApi)
                foreach ($offsets as $offset) {
                    $state = $this->cache->getOffsetState($source, $offset["version"],
                        $offset["entry"]["offset"]);

                    // no synchronization yet
                    // create sub sync request
                    if ($state === false) {
                        $sync = new LocalOffsetRequest($this->id, $this->cache,
                            $source, $api, $offset["version"], $offset["entry"]);
                        $this->queues["local"][$this->id] = $sync;

                        $sync->addCacheId($id);
                        $request->addSyncId($this->id++);

                        // redundant
                        // recycle active sync request ID
                    } elseif (is_int($state)) {
                        $this->queues["local"][$state]->addCacheId($id);
                        $request->addSyncId($state);
                    }
                }

            $state = $this->cache->getReferencesState($source);

            // no synchronization yet
            // create sub sync request
            if ($state === false) {
                $sync = new LocalReferencesRequest($this->id, $this->cache, $source, $api);
                $this->queues["local"][$this->id] = $sync;

                $sync->addCacheId($id);
                $request->addSyncId($this->id++);

                // redundant
                // recycle active sync request ID
            } elseif (is_int($state)) {
                $this->queues["local"][$state]->addCacheId($id);
                $request->addSyncId($state);
            }

            // async/lazy remote
        } else {
            if ($api instanceof RemoteOffsetApi)
                foreach ($offsets as $offset) {
                    $state = $this->cache->getOffsetState($source, $offset["version"],
                        $offset["entry"]["offset"]);

                    // no synchronization yet
                    // create sub sync request
                    if ($state === false) {
                        $sync = new RemoteOffsetRequest($this->id, $this->cache,
                            $source, $api, $offset["version"], $offset["entry"]);

                        $this->addRemoteRequest($api, $sync);
                        $sync->addCacheId($id);
                        $request->addSyncId($this->id++);

                        // redundant
                        // recycle active sync request ID
                    } elseif (is_int($state)) {
                        $this->queues["remote"][$state]->addCacheId($id);
                        $request->addSyncId($state);
                    }
                }

            $state = $this->cache->getReferencesState($source);

            // no synchronization yet
            // create sub sync request
            if ($state === false) {
                $sync = new RemoteReferencesRequest($this->id, $this->cache, $source, $api);

                $this->addRemoteRequest($api, $sync);
                $sync->addCacheId($id);
                $request->addSyncId($this->id++);

                // redundant
                // recycle active sync request ID
            } elseif (is_int($state)) {
                $this->queues["remote"][$state]->addCacheId($id);
                $request->addSyncId($state);
            }
        }

        // cache request ID
        // keep sync local/remote request here
        return $id;
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
        return $this->addFileRequest($source,

            // allow only json and
            // only important file request
            // block dynamic files
            "","/fusion.json");
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
        return $this->addFileRequest($source,

            // allow only json and
            // only important file request
            // block dynamic files
            $path, "/snapshot.json");
    }

    /**
     * Enqueues file request.
     *
     * @param array $source Source + pointer.
     * @param string $file Relative to the package root file.
     * @return int Unique request ID.
     * @throws HubError Hub exception.
     */
    protected function addFileRequest(array $source, string $path, string $file): int
    {
        $api = $this->apis[$source["api"]] ?? null;

        // enqueue lazy drop
        // return ID for error path first
        // support detailed exceptions
        if ($api === null)
            return $this->addErrorRequest($source);

        // visible external request
        $id = $this->id++;
        $request = new CacheFileRequest($id, $this->cache, $source, $path, $file, $api);
        $this->queues["cache"][$id] = $request;

        $state = $this->cache->getFileState($source, $file, $api);

        // no synchronization yet
        // create sub sync request
        if ($state === false) {
            if ($api instanceof LocalApi) {
                $sync = new LocalFileRequest($this->id, $this->cache, $source,
                    $path, $file, $api);

                $this->queues["local"][$this->id] = $sync;

            } else {
                $sync = new RemoteFileRequest($this->id, $this->cache, $source,
                    $path, $file, $api);

                $this->addRemoteRequest($api, $sync);
            }

            $sync->addCacheId($id);
            $request->addSyncId($this->id++);

            // redundant
            // recycle active sync request ID
        } elseif (is_int($state)) {
            $sync = $this->queues["remote"][$state] ??
                $this->queues["local"][$state];

            $sync->addCacheId($id);
            $request->addSyncId($state);
        }

        // cache request ID
        // keep sync local/remote request here
        return $id;
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
        $api = $this->apis[$source["api"]] ?? null;

        // enqueue lazy drop
        // return ID for error path first
        // support detailed exceptions
        if ($api === null)
            return $this->addErrorRequest($source);

        // visible external request
        $id = $this->id++;
        $request = new CacheArchiveRequest($id, $this->cache, $source, $api);
        $this->queues["cache"][$id] = $request;

        $state = $this->cache->getFileState($source, "/archive.zip", $api);

        // no synchronization yet
        // create sub sync request
        if ($state === false) {
            if ($api instanceof LocalApi) {
                $sync = new LocalArchiveRequest($this->id, $this->cache,
                    $source, $api);

                $this->queues["local"][$this->id] = $sync;

            } else {
                $sync = new RemoteArchiveRequest($this->id, $this->cache,
                    $source, $api);

                $this->addRemoteRequest($api, $sync);
            }

            $sync->addCacheId($id);
            $request->addSyncId($this->id++);

            // redundant
            // recycle active sync request ID
        } elseif (is_int($state)) {
            $sync = $this->queues["remote"][$state] ??
                $this->queues["local"][$state];

            $sync->addCacheId($id);
            $request->addSyncId($state);
        }

        // cache request ID
        // keep sync local/remote request here
        return $id;
    }

    /**
     * Adds remote request.
     *
     * @param RemoteApi $api API.
     * @param RemoteRequest $request Request.
     * @throws HubError Hub exception.
     */
    protected function addRemoteRequest(RemoteApi $api, RemoteRequest $request): void
    {
        $this->queues["remote"][$this->id] = $request;
        $handle = $request->getHandle();

        if (!curl_setopt($handle, CURLOPT_SHARE, $this->shareHandle))
            $this->dropCurlError();

        // prevent polling and
        // respect rate limit
        if ($api->hasDelay())
            $api->addDelayRequest($this->id);

        elseif (curl_multi_add_handle($this->handle, $handle))
            $this->dropCurlError();
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
        while ($this->queues["cache"]) {
            foreach ($this->queues["cache"] as $id => $request)

                // synchronized
                if (!$request->hasSyncIds()) {
                    $request->response($callback);
                    unset($this->queues["cache"][$id]);
                }

            // local sub request
            // write to cache before response
            foreach ($this->queues["local"] as $syncId => $request) {
                $request->execute();

                foreach ($request->getCacheIds() as $cacheId)
                    $this->queues["cache"][$cacheId]->removeSyncId($syncId);

                unset($this->queues["local"][$syncId]);
            }

            // execution state as group
            // individuals may still have errors
            // wait until any pulse or
            // timeout block
            if (curl_multi_exec($this->handle, $tail) ||
                curl_multi_select($this->handle) == -1)
                $this->dropCurlError();

            // evaluate responses
            while ($info = curl_multi_info_read($this->handle)) {
                $id = curl_getinfo($info["handle"], CURLINFO_PRIVATE);
                $request = $this->queues["remote"][$id];
                $lifecycle = $request->getLifecycle($info["result"],

                    // archive has no content
                    // normalize
                    curl_multi_getcontent($info["handle"]) ??
                    "no content");

                if (curl_multi_remove_handle($this->handle, $info["handle"]))
                    $this->dropCurlError();

                // check lifecycle
                // some request are not primitive
                if ($lifecycle == Lifecycle::DONE) {
                    foreach ($request->getCacheIds() as $cacheId)
                        $this->queues["cache"][$cacheId]->removeSyncId($id);

                    unset($this->queues["remote"][$id]);

                    // pagination, token, ...
                } elseif ($lifecycle == Lifecycle::RELOAD) {
                    if (curl_multi_add_handle($this->handle, $info["handle"]))
                        $this->dropCurlError();

                    $tail++;
                }
            }

            // reload limited
            foreach ($this->apis as $api)
                if ($api instanceof RemoteApi && $api->hasDelay()) {
                    $delay = $api->getDelay();

                    if ($delay["timestamp"] <= time()) {
                        foreach ($delay["requests"] as $id) {
                            if (curl_multi_add_handle($this->handle,

                                // reload again
                                $this->queues["remote"][$id]->getHandle()))
                                $this->dropCurlError();

                            $tail++;
                        }

                        $api->resetDelay();
                    }
                }

            // only idle remote queue left
            // trigger delay
            if ($tail == 0 && $this->queues["remote"] && !$this->queues["local"]) {

                // + 1 hour should be enough max
                $timestamp = time() + 3600;
                $syncId = 0;

                // take next
                foreach ($this->apis as $api)
                    if ($api instanceof RemoteApi && $api->hasDelay()) {
                        $delay = $api->getDelay();

                        if ($delay["timestamp"] < $timestamp) {
                            $timestamp = $delay["timestamp"];
                            $syncId = $delay["requests"][0];
                        }
                    }

                // calc approximately
                $delay = abs($timestamp - time());
                $request = $this->queues["remote"][$syncId];

                // do not spam
                // only noticeable delays
                if ($delay > 10)
                    Log::notice(new RequestError(
                        $request->getCacheIds()[0],
                        "Rate limit exceeded. The API blocks " .
                        "all queued requests until \"" .
                        date("H:i:s", time() + $delay) .
                        " ($delay sec)\" - waiting ...",
                        [$request->getUrl()]
                    ));

                sleep($delay);
            }
        }
    }

    /**
     * Throws multi cURL hub error.
     *
     * @throws HubError Hub exception.
     */
    protected function dropCurlError(): void
    {
        throw new HubError(
            curl_multi_strerror(
                curl_multi_errno($this->handle)
            )
        );
    }
}