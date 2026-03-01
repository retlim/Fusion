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

namespace Valvoid\Fusion\Tests\Units\Hub\APIs\Remote\Valvoid;

use Valvoid\Fusion\Hub\APIs\Remote\Status;
use Valvoid\Fusion\Hub\APIs\Remote\Valvoid\Valvoid;
use Valvoid\Reflex\Test\Wrapper;

class ValvoidTest extends Wrapper
{
    private array $config = [
        "protocol" => "https",
        "domain" => "valvoid.com",
        "url" => "https://api.valvoid.com/v1/registry",
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
        $api = new Valvoid($this->config);
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
        $api = new Valvoid($this->config);

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
        $api = new Valvoid($this->config);
        $time = time();

        $this->validate($api->getRateLimitReset(["x-rate-limit-reset: $time"], ""))
            ->as($time);

        // fallback
        $this->validate($api->getRateLimitReset([], ""))
            ->as($time + 60);
    }

    public function testArchiveUrl(): void
    {
        $api = new Valvoid($this->config);

        // path has leading slash
        $this->validate($api->getArchiveUrl("/valvoid/fusion",
                "1.2.3+346"))
            ->as($this->config["url"] .

            // semver and
            // path as encoded package identifier
            "/valvoid%2Ffusion/1.2.3%2B346/archive.zip");
    }

    public function testFileUrl(): void
    {
        $api = new Valvoid($this->config);

        // path/file leading slash
        $this->validate($api->getFileUrl("/valvoid/fusion",
                "1.2.3-beta+346", "/fusion.json"))
            ->as($this->config["url"] .

            // semver and
            // path as encoded package identifier
            "/valvoid%2Ffusion/1.2.3-beta%2B346/fusion.json");
    }

    public function testTokens(): void
    {
        $api = new Valvoid($this->config);

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
        $api = new Valvoid($this->config);

        $this->validate($api->getAuthHeaderPrefix())
            ->as("Authorization: Bearer ");
    }

    public function testOptions(): void
    {
        $api = new Valvoid($this->config);

        // default
        $options = [CURLOPT_HTTPHEADER => [
            "accept: application/json"
        ]];

        $this->validate($api->getReferencesOptions())
            ->as($options);

        $this->validate($api->getArchiveOptions())
            ->as($options);

        $this->validate($api->getFileOptions())
            ->as($options);

        // dev/local/whatever http
        $this->config["protocol"] = "http";
        $api = new Valvoid($this->config);
        $options = [CURLOPT_HTTPHEADER => [
                "accept: application/json"],
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0];

        $this->validate($api->getReferencesOptions())
            ->as($options);

        $this->validate($api->getArchiveOptions())
            ->as($options);

        $this->validate($api->getFileOptions())
            ->as($options);
    }

    public function testError(): void
    {
        $api = new Valvoid($this->config);

        $this->validate($api->getErrorMessage(404, [],

                // API json response
                json_encode(["message" => "test"])))
            ->as("404 | test");

        if (str_starts_with($api->getErrorMessage(500, [],

                // can not parse
                // server whatever response
                // try debug log
                ""), "500 |") === false)
            $this->fail("Error message does not start with '500'");
    }

    public function testReferencesUrl(): void
    {
        $api = new Valvoid($this->config);

        // path has leading slash
        $this->validate($api->getReferencesUrl("/valvoid/fusion"))
            ->as($this->config["url"] .

                // path as encoded package identifier
                "/valvoid%2Ffusion/versions?per_page=100");
    }

    public function testReferences(): void
    {
        $api = new Valvoid($this->config);

        $content = ["ref"];
        $references = $api->getReferences("", [
            'link: <https://api.valvoid.com>; rel="next"',
        ], $content);

        // pagination
        // asset next api link to get more references
        $this->validate($references->getUrl())
            ->as("https://api.valvoid.com");

        $this->validate($references->getEntries())
            ->as($content);

        // last page or
        // next link is optional
        // no pagination
        $references = $api->getReferences("", [], $content);

        $this->validate($references->getUrl())
            ->asNull();

        $this->validate($references->getEntries())
            ->as($content);
    }
}