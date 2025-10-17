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

namespace Valvoid\Fusion\Tests\Hub\APIs\Remote\Valvoid;

use Valvoid\Fusion\Hub\APIs\Remote\Status;
use Valvoid\Fusion\Hub\APIs\Remote\Valvoid\Valvoid;
use Valvoid\Fusion\Tests\Test;

class ValvoidTest extends Test
{
    /** @var string|array Code coverage. */
    protected string|array $coverage = Valvoid::class;

    /** @var Valvoid API. */
    private Valvoid $api;

    /** @var array Default config. */
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

    public function __construct()
    {
        $this->api = new Valvoid($this->config);

        $this->testReferencesUrl();
        $this->testReferences();
        $this->testError();
        $this->testAuthHeaderPrefix();
        $this->testTokens();
        $this->testFileUrl();
        $this->testArchiveUrl();
        $this->testDelay();
        $this->testRate();
        $this->testStatus();
        $this->testOptions();
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

        if ($this->api->getRateLimitReset(["x-rate-limit-reset: $time"],

                // ballast
                "") !== $time)
            $this->handleFailedTest();

        // fallback
        if ($this->api->getRateLimitReset([],

                // ballast
                "") !== $time + 60)
            $this->handleFailedTest();
    }

    public function testArchiveUrl(): void
    {
        // path has leading slash
        if ($this->api->getArchiveUrl("/valvoid/fusion",
                "1.2.3+346") !==
            $this->config["url"] .

            // semver and
            // path as encoded package identifier
            "/valvoid%2Ffusion/1.2.3%2B346/archive.zip")
            $this->handleFailedTest();
    }

    public function testFileUrl(): void
    {
        // path/file leading slash
        if ($this->api->getFileUrl("/valvoid/fusion",
                "1.2.3-beta+346", "/fusion.json") !==
            $this->config["url"] .

            // semver and
            // path as encoded package identifier
            "/valvoid%2Ffusion/1.2.3-beta%2B346/fusion.json")
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
                "accept: application/json"
            ]];

        if ($this->api->getReferencesOptions() !== $options)
            $this->handleFailedTest();

        if ($this->api->getArchiveOptions() !== $options)
            $this->handleFailedTest();

        if ($this->api->getFileOptions() !== $options)
            $this->handleFailedTest();

        // dev/local/whatever http
        $this->config["protocol"] = "http";
        $this->api = new Valvoid($this->config);
        $options = [
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => [
                "accept: application/json"
            ]];

        if ($this->api->getReferencesOptions() != $options)
            $this->handleFailedTest();

        if ($this->api->getArchiveOptions() != $options)
            $this->handleFailedTest();

        if ($this->api->getFileOptions() != $options)
            $this->handleFailedTest();
    }

    public function testError(): void
    {
        if ($this->api->getErrorMessage(404, [],

            // API json response
            json_encode(["message" => "test"])) !== "404 | test")
            $this->handleFailedTest();

        if (str_starts_with($this->api->getErrorMessage(500, [],

            // can not parse
            // server whatever response
            // try debug log
            ""), "500 |") === false)
            $this->handleFailedTest();
    }

    public function testReferencesUrl(): void
    {
        // path has leading slash
        if ($this->api->getReferencesUrl("/valvoid/fusion") !==
            $this->config["url"] .

            // path as encoded package identifier
            "/valvoid%2Ffusion/versions?per_page=100")
            $this->handleFailedTest();
    }

    public function testReferences(): void
    {
        $content = ["ref"];
        $references = $this->api->getReferences("", [
                'link: <https://api.valvoid.com>; rel="next"',
            ], $content);

        // pagination
        // asset next api link to get more references
        if ($references->getUrl() !== "https://api.valvoid.com" ||
            $references->getEntries() !== $content)
            $this->handleFailedTest();

        // last page or
        // next link is optional
        // no pagination
        $references = $this->api->getReferences("", [], $content);

        if ($references->getUrl() !== null ||
            $references->getEntries() !== $content)
            $this->handleFailedTest();
    }
}