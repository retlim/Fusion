<?php
/**
 * Fusion - PHP Package Manager
 * Copyright © Valvoid
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

namespace Valvoid\Fusion\Tests\Hub\APIs\Remote\Bitbucket;

use Valvoid\Fusion\Hub\APIs\Remote\Bitbucket\Bitbucket;
use Valvoid\Fusion\Hub\APIs\Remote\Status;
use Valvoid\Fusion\Tests\Test;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class BitbucketTest extends Test
{
    protected string|array $coverage = Bitbucket::class;

    /** @var Bitbucket API. */
    private Bitbucket $api;

    /** @var array Default config. */
    private array $config = [
        "protocol" => "https",
        "domain" => "bitbucket.org",
        "url" => "https://api.bitbucket.org/2.0/repositories",
        "version" => 2.0,
        "tokens" => [
            "token1",
            "valvoid" => [
                "fusion" => "token2",
                "token3"
            ],
            "token4"
        ]
    ];

    public function __construct()
    {
        $this->api = new Bitbucket($this->config);

        // 2.0 cloud
        $this->testCloudReferencesUrl();
        $this->testCloudReferences();
        $this->testCloudError();
        $this->testAuthHeaderPrefix();
        $this->testTokens();
        $this->testCloudFileUrl();
        $this->testCloudArchiveUrl();
        $this->testDelay();
        $this->testRate();
        $this->testStatus();
        $this->testOptions();

        $this->config["url"] = "https://whatever.com/rest/api/latest/projects";
        $this->config["version"] = 1.0;
        $this->api = new Bitbucket($this->config);

        // 1.0 server
        $this->testServerReferencesUrl();
        $this->testServerReferences();
        $this->testServerError();
        $this->testServerFileUrl();
        $this->testServerArchiveUrl();
    }

    public function testDelay(): void
    {
        $time = time();

        // 429 to many request
        // first active request triggers delay
        $this->api->setDelay($time, 1);

        // rate limit
        if ($this->api->hasDelay() !== true)
            $this->handleFailedTest();

        // add other requests
        $this->api->addDelayRequest(2);

        // retry paused
        if($this->api->getDelay() !== [
                "timestamp" => $time,
                "requests" => [1, 2]
            ]) $this->handleFailedTest();

        // clear
        $this->api->resetDelay();

        // rate limit
        if ($this->api->hasDelay() !== false)
            $this->handleFailedTest();
    }

    public function testStatus(): void
    {
        if ($this->api->getStatus(200, []) !== Status::OK)
            $this->handleFailedTest();

        if ($this->api->getStatus(401, []) !== Status::UNAUTHORIZED)
            $this->handleFailedTest();

        if ($this->api->getStatus(403, []) !== Status::FORBIDDEN)
            $this->handleFailedTest();

        if ($this->api->getStatus(404, []) !== Status::NOT_FOUND)
            $this->handleFailedTest();

        if ($this->api->getStatus(429, []) !== Status::TO_MANY_REQUESTS)
            $this->handleFailedTest();

        if ($this->api->getStatus(500, []) !== Status::ERROR)
            $this->handleFailedTest();
    }

    public function testRate(): void
    {
        $time = time();

        if ($this->api->getRateLimitReset(["x-ratelimit-reset: $time"],

                // ballast
                "") !== $time)
            $this->handleFailedTest();

        if ($this->api->getRateLimitReset(["retry-after: $time"],

                // ballast
                "") <= $time)
            $this->handleFailedTest();

        // fallback
        if ($this->api->getRateLimitReset([],

                // ballast
                "") !== $time + 60)
            $this->handleFailedTest();
    }

    public function testCloudArchiveUrl(): void
    {
        // path has leading slash
        if ($this->api->getArchiveUrl("/valvoid/fusion",
                "1.2.3+346") !==

            // no API endpoint and
            // encoded semver
            "https://bitbucket.org/valvoid/fusion/get/1.2.3%2B346.zip")
            $this->handleFailedTest();
    }

    public function testServerArchiveUrl(): void
    {
        // path has leading slash
        if ($this->api->getArchiveUrl("/valvoid/fusion",
                "1.2.3+346") !==
            $this->config["url"] .

            // encoded semver
            "/valvoid/repos/fusion/archive?at=1.2.3%2B346&format=zip")
            $this->handleFailedTest();
    }

    public function testCloudFileUrl(): void
    {
        // path/file leading slash
        if ($this->api->getFileUrl("/valvoid/fusion",
                "1.2.3-beta+346", "/fusion.json") !==
            $this->config["url"] .

            // encoded semver
            "/valvoid/fusion/src/1.2.3-beta%2B346/fusion.json")
            $this->handleFailedTest();
    }

    public function testServerFileUrl(): void
    {
        // path/file leading slash
        if ($this->api->getFileUrl("/valvoid/fusion",
                "1.2.3-beta+346", "/fusion.json") !==
            $this->config["url"] .

            // encoded semver
            "/valvoid/repos/fusion/rawfusion.json?at=1.2.3-beta%2B346")
            $this->handleFailedTest();
    }

    public function testTokens(): void
    {
        if ($this->api->getTokens("/valvoid/whatever") !==

            // path first order
            // matched tokens (token3) are higher and
            // first to consume
            ["token3", "token1", "token4"])
            $this->handleFailedTest();

        // do not reuse invalid tokens
        $this->api->addInvalidToken("token3");
        $this->api->addInvalidToken("token4");

        if ($this->api->getTokens("/valvoid") !== ["token1"])
            $this->handleFailedTest();
    }

    public function testAuthHeaderPrefix(): void
    {
        if ($this->api->getAuthHeaderPrefix() !== "Authorization: Bearer ")
            $this->handleFailedTest();
    }

    public function testOptions(): void
    {
        // default
        $options = [CURLOPT_HTTPHEADER => [
            "Accept: application/json"
        ]];

        if ($this->api->getReferencesOptions() !== $options)
            $this->handleFailedTest();

        if ($this->api->getArchiveOptions() !== $options)
            $this->handleFailedTest();

        if ($this->api->getFileOptions() !== $options)
            $this->handleFailedTest();

        // dev/local/whatever http
        $this->config["protocol"] = "http";
        $this->api = new Bitbucket($this->config);
        $options = [
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => [
                "Accept: application/json"
            ]];

        if ($this->api->getReferencesOptions() != $options)
            $this->handleFailedTest();

        if ($this->api->getArchiveOptions() != $options)
            $this->handleFailedTest();

        if ($this->api->getFileOptions() != $options)
            $this->handleFailedTest();
    }

    public function testCloudError(): void
    {
        if ($this->api->getErrorMessage(404, [],

                // API json response
                json_encode(["error" => ["message" => "test"]])) !== "404 | test")
            $this->handleFailedTest();

        if (str_starts_with($this->api->getErrorMessage(500, [],

                // can not parse
                // server whatever response
                // try debug log
                ""), "500 |") === false)
            $this->handleFailedTest();
    }

    public function testServerError(): void
    {
        if ($this->api->getErrorMessage(404, [],

                // API json response
                json_encode(["errors" => [["message" => "test"]]])) !== "404 | test")
            $this->handleFailedTest();

        if (str_starts_with($this->api->getErrorMessage(500, [],

                // can not parse
                // server whatever response
                // try debug log
                ""), "500 |") === false)
            $this->handleFailedTest();
    }

    public function testCloudReferencesUrl(): void
    {
        // path has leading slash
        if ($this->api->getReferencesUrl("/valvoid/fusion") !==
            $this->config["url"] .

            // raw as it is
            "/valvoid/fusion/refs/tags?pagelen=100")
            $this->handleFailedTest();
    }

    public function testServerReferencesUrl(): void
    {
        // path has leading slash
        if ($this->api->getReferencesUrl("/valvoid/fusion") !==
            $this->config["url"] .

            // injected
            "/valvoid/repos/fusion/tags?limit=100")
            $this->handleFailedTest();
    }

    public function testCloudReferences(): void
    {
        $content = ["ref"];
        $references = $this->api->getReferences("", [], [
            "values" => [["name" => "ref"]],
            "next" => "https://bitbucket.org"
        ]);

        // pagination
        // asset next api link to get more references
        if ($references->getUrl() !== "https://bitbucket.org" ||
            $references->getEntries() !== $content)
            $this->handleFailedTest();

        // last page or
        // next link is optional
        // no pagination
        $references = $this->api->getReferences("", [], [
            "values" => [["name" => "ref"]],
        ]);

        if ($references->getUrl() !== null ||
            $references->getEntries() !== $content)
            $this->handleFailedTest();
    }

    public function testServerReferences(): void
    {
        $content = ["ref"];
        $references = $this->api->getReferences("/valvoid/fusion", [], [
            "values" => [["displayId" => "ref"]],
            "isLastPage" => false,
            "start" => "##"
        ]);

        // pagination
        // asset next api link to get more references
        if ($references->getUrl() !== $this->config["url"] .
            "/valvoid/repos/fusion/tags?limit=100&start=##" ||
            $references->getEntries() !== $content)
            $this->handleFailedTest();

        // last page or
        // next link is optional
        // no pagination
        $references = $this->api->getReferences("", [], [
            "values" => [["displayId" => "ref"]],
            "isLastPage" => true
        ]);

        if ($references->getUrl() !== null ||
            $references->getEntries() !== $content)
            $this->handleFailedTest();
    }
}