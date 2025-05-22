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

namespace Valvoid\Fusion\Tests\Hub;

use Exception;
use Valvoid\Fusion\Hub\Hub;
use Valvoid\Fusion\Hub\Proxy\Logic;
use Valvoid\Fusion\Hub\Responses\Cache\Archive;
use Valvoid\Fusion\Hub\Responses\Cache\Archive as CacheArchive;
use Valvoid\Fusion\Hub\Responses\Cache\Metadata;
use Valvoid\Fusion\Hub\Responses\Cache\Snapshot;
use Valvoid\Fusion\Hub\Responses\Cache\Versions;
use Valvoid\Fusion\Hub\Responses\Local\Archive as LocalArchive;
use Valvoid\Fusion\Hub\Responses\Local\File;
use Valvoid\Fusion\Hub\Responses\Local\Offset as LocalOffset;
use Valvoid\Fusion\Hub\Responses\Local\References as LocalReferences;
use Valvoid\Fusion\Hub\Responses\Remote\Offset as RemoteOffset;
use Valvoid\Fusion\Hub\Responses\Remote\References as RemoteReferences;
use Valvoid\Fusion\Log\Events\Errors\Request;
use Valvoid\Fusion\Tests\Hub\Mocks\ContainerMock;
use Valvoid\Fusion\Tests\Test;
use Valvoid\Fusion\Wrappers\CurlMulti;
use Valvoid\Fusion\Wrappers\CurlShare;

/**
 * Hub test.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class HubTest extends Test
{
    protected string|array $coverage = [
        Hub::class,

        // ballast
        RemoteReferences::class,
        RemoteOffset::class,
        LocalReferences::class,
        LocalOffset::class,
        File::class,
        LocalArchive::class,
        Versions::class,
        Snapshot::class,
        Metadata::class,
        CacheArchive::class,
        CurlShare::class,
        CurlMulti::class
    ];

    private ContainerMock $container;

    public function __construct()
    {
        $this->container = new ContainerMock;

        // static
        $this->container->setUpStaticTests();
        $this->testStaticInterface();

        // logic
        $this->container->setUpLogicTests();
        $this->testErrorRequest();

        // all request are cache first and
        // synchronizable
        $this->testVersionsCacheRequest();
        $this->testFileCacheRequest();
        $this->testArchiveCacheRequest();

        $this->container->destroy();
    }

    public function testArchiveCacheRequest(): void
    {
        $hub = new Logic;
        $source = [
            "api" => "test",
            "path" => "/-",
            "reference" => "1.2.3",
            "prefix" => ""
        ];

        try {
            $id = $hub->addArchiveRequest($source);
            $hub->executeRequests(function (Archive $archive) use (&$id) {
                if ($archive->getId() !== $id ||
                    $archive->getFile() !== "/archive.zip")
                    $this->handleFailedTest();

                // hook triggered
                $id = true;
            });

            if ($id !== true)
                $this->handleFailedTest();

        // mock API has no logic and
        // drops error when sync request
        } catch (Exception) {
            $this->handleFailedTest();
        }
    }

    public function testFileCacheRequest(): void
    {
        $hub = new Logic;
        $source = [
            "api" => "test",
            "path" => "/-",
            "reference" => "1.2.3",
            "prefix" => ""
        ];

        try {
            $id = $hub->addMetadataRequest($source);
            $hub->executeRequests(function (Metadata $metadata) use (&$id) {
                if ($metadata->getId() !== $id ||
                    $metadata->getFile() !== "/###" ||
                    $metadata->getContent() !== "###")
                    $this->handleFailedTest();

                // hook triggered
                $id = true;
            });

            if ($id !== true)
                $this->handleFailedTest();

            $id = $hub->addSnapshotRequest($source, "/-");
            $hub->executeRequests(function (Snapshot $snapshot) use (&$id) {
                if ($snapshot->getId() !== $id ||
                    $snapshot->getFile() !== "/###" ||
                    $snapshot->getContent() !== "###")
                    $this->handleFailedTest();

                // hook triggered
                $id = true;
            });

            if ($id !== true)
                $this->handleFailedTest();

        // mock API has no logic and
        // drops error when sync request
        } catch (Exception) {
            $this->handleFailedTest();
        }
    }

    public function testVersionsCacheRequest(): void
    {
        $hub = new Logic;
        $source = [
            "api" => "test",
            "path" => "/-",
            "reference" => [[
                "major" => 1,
                "minor" => 2,
                "patch" => 3,
                "release" => "",
                "build" => ""
            ]]
        ];

        try {
            $id = $hub->addVersionsRequest($source);
            $hub->executeRequests(function (Versions $versions) use (&$id) {
                if ($versions->getId() !== $id ||
                    $versions->getEntries() !== [
                        "1.3.4",
                        "1.2.3"
                    ])
                    $this->handleFailedTest();

                // hook triggered
                $id = true;
            });

            if ($id !== true)
                $this->handleFailedTest();

        // mock API has no logic and
        // drops error when sync request
        } catch (Exception) {
            $this->handleFailedTest();
        }
    }

    public function testErrorRequest(): void
    {
        // unknown api
        $source = ["api" => "whatever"];
        $hub = new Logic;

        try {
            $hub->addVersionsRequest($source);
            $hub->executeRequests(function (){});

            // assert exception
            // unknown api
            $this->handleFailedTest();

        } catch (Exception $exception) {
            if (!($exception instanceof Request))
                $this->handleFailedTest();
        }

        try {
            $hub->addSnapshotRequest($source, "");
            $hub->executeRequests(function (){});

            // assert exception
            // unknown api
            $this->handleFailedTest();

        } catch (Exception $exception) {
            if (!($exception instanceof Request))
                $this->handleFailedTest();
        }

        try {
            $hub->addMetadataRequest($source);
            $hub->executeRequests(function (){});

            // assert exception
            // unknown api
            $this->handleFailedTest();

        } catch (Exception $exception) {
            if (!($exception instanceof Request))
                $this->handleFailedTest();
        }

        try {
            $hub->addArchiveRequest($source);
            $hub->executeRequests(function (){});

            // assert exception
            // unknown api
            $this->handleFailedTest();

        } catch (Exception $exception) {
            if (!($exception instanceof Request))
                $this->handleFailedTest();
        }
    }

    public function testStaticInterface(): void
    {
        Hub::addVersionsRequest([]);
        Hub::addMetadataRequest([]);
        Hub::addSnapshotRequest([],"");
        Hub::addArchiveRequest([]);
        Hub::executeRequests(function (){});

        // static functions connected to same non-static functions
        if ($this->container->hub->calls !== [
                "addVersionsRequest",
                "addMetadataRequest",
                "addSnapshotRequest",
                "addArchiveRequest",
                "executeRequests",])
            $this->handleFailedTest();
    }
}