<?php
/*
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
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Valvoid\Fusion\Tests\Units\Hub\APIs\Remote\Bitbucket;

use Valvoid\Fusion\Hub\APIs\Remote\Bitbucket\Bitbucket;
use Valvoid\Fusion\Hub\APIs\Remote\Status;
use Valvoid\Reflex\Test\Wrapper;

class BitbucketTest extends Wrapper
{
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

    public function testDelay(): void
    {
        $api = new Bitbucket($this->config);
        $time = time();

        // 429 to many request
        // first active request triggers delay
        $api->setDelay($time, 1);

        // rate limit
        $this->validate($api->hasDelay())
            ->as(true);

        // add other requests
        $api->addDelayRequest(2);

        // retry paused
        $this->validate($api->getDelay())
            ->as([
                "timestamp" => $time,
                "requests" => [1, 2]
            ]);

        // clear
        $api->resetDelay();

        // rate limit
        $this->validate($api->hasDelay())
            ->as(false);
    }

    public function testStatus(): void
    {
        $api = new Bitbucket($this->config);

        $this->validate($api->getStatus(200, []))
            ->as(Status::OK);

        $this->validate($api->getStatus(401, []))
            ->as(Status::UNAUTHORIZED);

        $this->validate($api->getStatus(403, []))
            ->as(Status::FORBIDDEN);

        $this->validate($api->getStatus(404, []))
            ->as(Status::NOT_FOUND);

        $this->validate($api->getStatus(429, []))
            ->as(Status::TO_MANY_REQUESTS);

        $this->validate($api->getStatus(500, []))
            ->as(Status::ERROR);
    }

    public function testRate(): void
    {
        $api = new Bitbucket($this->config);

        $this->validate($api->getRateLimitReset(["x-ratelimit-reset: 11"], ""))
            ->as(11);

        // fallback
        $this->validate($api->getRateLimitReset([],""))
            ->asInt();
    }

    public function testCloudArchiveUrl(): void
    {
        $api = new Bitbucket($this->config);

        // path has leading slash
        $this->validate($api->getArchiveUrl("/valvoid/fusion",
            "1.2.3+346"))

            // no API endpoint and
            // encoded semver
            ->as("https://bitbucket.org/valvoid/fusion/get/1.2.3%2B346.zip");
    }

    public function testCloudFileUrl(): void
    {
        $api = new Bitbucket($this->config);

        // path/file leading slash
        $this->validate($api->getFileUrl("/valvoid/fusion",
            "1.2.3-beta+346", "/fusion.json"))
            ->as($this->config["url"] .

                // encoded semver
                "/valvoid/fusion/src/1.2.3-beta%2B346/fusion.json");
    }

    public function testTokens(): void
    {
        $api = new Bitbucket($this->config);

        $this->validate($api->getTokens("/valvoid/whatever"))
            ->as(

            // path first order
            // matched tokens (token3) are higher and
            // first to consume
                ["token3", "token1", "token4"]);

        // do not reuse invalid tokens
        $api->addInvalidToken("token3");
        $api->addInvalidToken("token4");

        $this->validate($api->getTokens("/valvoid"))
            ->as(["token1"]);
    }

    public function testAuthHeaderPrefix(): void
    {
        $api = new Bitbucket($this->config);

        $this->validate($api->getAuthHeaderPrefix())
            ->as("Authorization: Bearer ");
    }

    public function testOptions(): void
    {
        $api = new Bitbucket($this->config);

        // default
        $options = [CURLOPT_HTTPHEADER => [
            "Accept: application/json"
        ]];

        $this->validate($api->getReferencesOptions())
            ->as($options);

        $this->validate($api->getArchiveOptions())
            ->as($options);

        $this->validate($api->getFileOptions())
            ->as($options);

        // dev/local/whatever http
        $this->config["protocol"] = "http";
        $api = new Bitbucket($this->config);
        $options = [
            CURLOPT_HTTPHEADER => ["Accept: application/json"],
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0];

        $this->validate($api->getReferencesOptions())
            ->as($options);

        $this->validate($api->getArchiveOptions())
            ->as($options);

        $this->validate($api->getFileOptions())
            ->as($options);
    }

    public function testCloudError(): void
    {
        $api = new Bitbucket($this->config);

        $this->validate($api->getErrorMessage(404, [],

            // API json response
            json_encode(["error" => ["message" => "test"]])))
            ->as("404 | test");

        if (str_starts_with($api->getErrorMessage(500, [],

                // can not parse
                // server whatever response
                // try debug log
                ""), "500 |") === false)
            $this->fail("Error message does not start with '500'");
    }

    public function testCloudReferencesUrl(): void
    {
        $api = new Bitbucket($this->config);

        // path has leading slash
        $this->validate($api->getReferencesUrl("/valvoid/fusion"))
            ->as($this->config["url"] .

                // raw as it is
                "/valvoid/fusion/refs/tags?pagelen=100");
    }

    public function testCloudReferences(): void
    {
        $api = new Bitbucket($this->config);

        $content = ["ref"];
        $references = $api->getReferences("", [], [
            "values" => [["name" => "ref"]],
            "next" => "https://bitbucket.org"
        ]);

        // pagination
        // asset next api link to get more references
        $this->validate($references->getUrl())
            ->as("https://bitbucket.org");

        $this->validate($references->getEntries())
            ->as($content);

        // last page or
        // next link is optional
        // no pagination
        $references = $api->getReferences("", [], [
            "values" => [["name" => "ref"]],
        ]);

        $this->validate($references->getUrl())
            ->asNull();

        $this->validate($references->getEntries())
            ->as($content);
    }

    public function testCloudOffset(): void
    {
        $api = new Bitbucket($this->config);

        $this->validate($api->getOffset(["hash" => "###"])->getId())
            ->as("###");
    }

    public function testServerReferencesUrl(): void
    {
        $this->config["url"] = "https://whatever.com/rest/api/latest/projects";
        $this->config["version"] = 1.0;
        $api = new Bitbucket($this->config);

        // path has leading slash
        $this->validate($api->getReferencesUrl("/valvoid/fusion"))
            ->as($this->config["url"] .

                // injected
                "/valvoid/repos/fusion/tags?limit=100");
    }

    public function testServerReferences(): void
    {
        $api = new Bitbucket($this->config);

        $content = ["ref"];
        $references = $api->getReferences("/valvoid/fusion", [], [
            "values" => [["displayId" => "ref"]],
            "isLastPage" => false,
            "start" => "##"
        ]);

        // pagination
        // asset next api link to get more references
        $this->validate($references->getUrl())
            ->as($this->config["url"] .
                "/valvoid/repos/fusion/tags?limit=100&start=##");

        $this->validate($references->getEntries())
            ->as($content);

        // last page or
        // next link is optional
        // no pagination
        $references = $api->getReferences("", [], [
            "values" => [["displayId" => "ref"]],
            "isLastPage" => true
        ]);

        $this->validate($references->getUrl())
            ->asNull();

        $this->validate($references->getEntries())
            ->as($content);
    }

    public function testServerArchiveUrl(): void
    {
        $api = new Bitbucket($this->config);

        // path has leading slash
        $this->validate($api->getArchiveUrl("/valvoid/fusion", "1.2.3+346"))
            ->as($this->config["url"] .

                // encoded semver
                "/valvoid/repos/fusion/archive?at=1.2.3%2B346&format=zip");
    }

    public function testServerFileUrl(): void
    {
        $api = new Bitbucket($this->config);

        // path/file leading slash
        $this->validate($api->getFileUrl("/valvoid/fusion",
            "1.2.3-beta+346", "/fusion.json"))
            ->as($this->config["url"] .

                // encoded semver
                "/valvoid/repos/fusion/rawfusion.json?at=1.2.3-beta%2B346");
    }

    public function testServerError(): void
    {
        $api = new Bitbucket($this->config);

        $this->validate($api->getErrorMessage(404, [],

            // API json response
            json_encode(["errors" => [["message" => "test"]]])))
            ->as("404 | test");

        if (str_starts_with($api->getErrorMessage(500, [],

                // can not parse
                // server whatever response
                // try debug log
                ""), "500 |") === false)
            $this->fail("Error message does not start with '500'");
    }

    public function testServerOffset(): void
    {
        $api = new Bitbucket($this->config);

        $this->validate($api->getOffset(["id" => "###"])->getId())
            ->as("###");
    }
}