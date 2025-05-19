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
        CurlShare::class
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

        $this->container->destroy();
    }

    public function testErrorRequest(): void
    {
        // unknown api
        $hub = new Logic;

        try {
            $hub->addVersionsRequest(["api" => "whatever"]);
            $hub->executeRequests(function (){});

            // assert exception
            // unknown api
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;

        } catch (Exception $exception) {
            if (!($exception instanceof Request)) {
                echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

                $this->result = false;
            }
        }

        try {
            $hub->addSnapshotRequest(["api" => "whatever"], "");
            $hub->executeRequests(function (){});

            // assert exception
            // unknown api
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;

        } catch (Exception $exception) {
            if (!($exception instanceof Request)) {
                echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

                $this->result = false;
            }
        }

        try {
            $hub->addMetadataRequest(["api" => "whatever"]);
            $hub->executeRequests(function (){});

            // assert exception
            // unknown api
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;

        } catch (Exception $exception) {
            if (!($exception instanceof Request)) {
                echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

                $this->result = false;
            }
        }

        try {
            $hub->addArchiveRequest(["api" => "whatever"]);
            $hub->executeRequests(function (){});

            // assert exception
            // unknown api
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;

        } catch (Exception $exception) {
            if (!($exception instanceof Request)) {
                echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

                $this->result = false;
            }
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
                "executeRequests",]) {

            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}