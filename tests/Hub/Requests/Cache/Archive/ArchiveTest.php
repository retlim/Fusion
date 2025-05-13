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

namespace Valvoid\Fusion\Tests\Hub\Requests\Cache\Archive;

use Throwable;
use Valvoid\Fusion\Hub\Requests\Cache\Archive;
use Valvoid\Fusion\Tests\Hub\Requests\Cache\Archive\Mocks\APIMock;
use Valvoid\Fusion\Tests\Hub\Requests\Cache\Archive\Mocks\CacheMock;
use Valvoid\Fusion\Tests\Test;
use Valvoid\Fusion\Hub\Responses\Cache\Archive as ArchiveResponse;

/**
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ArchiveTest extends Test
{
    protected string|array $coverage = Archive::class;
    protected CacheMock $cacheMock;
    protected Archive $archive;
    protected APIMock $apiMock;
    protected array $source = [
        "api" => "test"
    ];

    public function __construct()
    {
        $this->cacheMock = new CacheMock;
        $this->apiMock = new APIMock;
        $this->archive = new Archive(1, $this->cacheMock,
            $this->source, $this->apiMock);

        $this->testInit();
        $this->testResponse();
    }

    public function testInit(): void
    {
        // sync request before cache
        $this->archive->addSyncId(5);

        if ($this->archive->hasSyncIds() === false)
            $this->handleFailedTest();

        $this->archive->removeSyncId(5);
        $this->archive->removeSyncId(1);

        if ($this->archive->hasSyncIds() !== false)
            $this->handleFailedTest();
    }

    public function testResponse(): void
    {
        try {
            $this->archive->response(function (ArchiveResponse $response) {
                if ($response->getId() !== 1 ||
                    $response->getFile() !== "##/archive.zip")
                    $this->handleFailedTest();
            });

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }
}