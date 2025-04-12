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

namespace Valvoid\Fusion\Tests\Hub\APIs\Remote\GitHub;

use Valvoid\Fusion\Hub\APIs\Remote\GitHub\GitHub;
use Valvoid\Fusion\Hub\APIs\Remote\Status;
use Valvoid\Fusion\Tests\Test;

/**
 * GitHub test.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class GitHubTest  extends Test
{
    protected string|array $coverage = GitHub::class;

    /** @var GitHub API. */
    private GitHub $api;

    /** @var array Default config. */
    private array $config = [
        "protocol" => "https",
        "domain" => "github.com",
        "url" => "https://api.github.com/repos",
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
        $this->api = new GitHub($this->config);

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
        if ($this->api->hasDelay() !== true) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " .

                // multi test pointer
                __LINE__;

            $this->result = false;
        }

        // add other requests
        $this->api->addDelayRequest(2);

        // retry paused
        if($this->api->getDelay() !== [
                "timestamp" => $time,
                "requests" => [1, 2]
            ]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " .

                // multi test pointer
                __LINE__;

            $this->result = false;
        }

        // clear
        $this->api->resetDelay();

        // rate limit
        if ($this->api->hasDelay() !== false) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " .

                // multi test pointer
                __LINE__;

            $this->result = false;
        }
    }

    public function testStatus(): void
    {
        if ($this->api->getStatus(200, []) !== Status::OK) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " .

                // multi test pointer
                __LINE__;

            $this->result = false;
        }

        if ($this->api->getStatus(401, []) !== Status::UNAUTHORIZED) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " .

                // multi test pointer
                __LINE__;

            $this->result = false;
        }

        if ($this->api->getStatus(403, []) !== Status::FORBIDDEN) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " .

                // multi test pointer
                __LINE__;

            $this->result = false;
        }

        if ($this->api->getStatus(404, []) !== Status::NOT_FOUND) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " .

                // multi test pointer
                __LINE__;

            $this->result = false;
        }

        if ($this->api->getStatus(429, []) !== Status::TO_MANY_REQUESTS) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " .

                // multi test pointer
                __LINE__;

            $this->result = false;
        }

        if ($this->api->getStatus(500, []) !== Status::ERROR) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " .

                // multi test pointer
                __LINE__;

            $this->result = false;
        }
    }

    public function testRate(): void
    {
        $time = time();

        if ($this->api->getRateLimitReset(["x-ratelimit-reset: $time"],

                // ballast
                "") !== $time) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }

        if ($this->api->getRateLimitReset(["retry-after: $time"],

                // ballast
                "") <= $time) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }

        // fallback
        if ($this->api->getRateLimitReset([],

                // ballast
                "") !== $time + 60) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testArchiveUrl(): void
    {
        // path has leading slash
        if ($this->api->getArchiveUrl("/valvoid/fusion",
                "1.2.3+346") !==
            $this->config["url"] .

            // encoded semver
            "/valvoid/fusion/zipball/1.2.3%2B346") {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testFileUrl(): void
    {
        // path/file leading slash
        if ($this->api->getFileUrl("/valvoid/fusion",
                "1.2.3-beta+346", "/fusion.json") !==
            $this->config["url"] .

            // encoded semver and
            // path as repo identifier
            "/valvoid/fusion/contents/fusion.json?ref=1.2.3-beta%2B346") {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testTokens(): void
    {
        if ($this->api->getTokens("/valvoid/whatever") !==

            // path first order
            // matched tokens (token3) are higher and
            // first to consume
            ["token3", "token1", "token4"]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " .

                // multi test pointer
                __LINE__;

            $this->result = false;
        }

        // do not reuse invalid tokens
        $this->api->addInvalidToken("token3");
        $this->api->addInvalidToken("token4");

        if ($this->api->getTokens("/valvoid") !== ["token1"]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " .

                // multi test pointer
                __LINE__;

            $this->result = false;
        }
    }

    public function testAuthHeaderPrefix(): void
    {
        if ($this->api->getAuthHeaderPrefix() !== "Authorization: Bearer ") {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testOptions(): void
    {
        // default
        $options = [CURLOPT_HTTPHEADER => [
            "user-agent: Fusion",
            "accept: application/vnd.github+json"
        ]];

        if ($this->api->getReferencesOptions() !== $options) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " .

                // multi test pointer
                __LINE__;

            $this->result = false;
        }

        if ($this->api->getArchiveOptions() !== $options) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " .

                // multi test pointer
                __LINE__;

            $this->result = false;
        }

        if ($this->api->getFileOptions() !== [CURLOPT_HTTPHEADER => [
                "user-agent: Fusion",
                "accept: application/vnd.github.raw+json"
            ]]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " .

                // multi test pointer
                __LINE__;

            $this->result = false;
        }

        // dev/local/whatever http
        $this->config["protocol"] = "http";
        $this->api = new GitHub($this->config);
        $options = [
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => [
                "user-agent: Fusion",
                "accept: application/vnd.github+json"
            ]];

        if ($this->api->getReferencesOptions() != $options) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " .

                // multi test pointer
                __LINE__;

            $this->result = false;
        }

        if ($this->api->getArchiveOptions() != $options) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " .

                // multi test pointer
                __LINE__;

            $this->result = false;
        }

        if ($this->api->getFileOptions() != [
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_HTTPHEADER => [
                    "user-agent: Fusion",
                    "accept: application/vnd.github.raw+json"
                ]]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " .

                // multi test pointer
                __LINE__;

            $this->result = false;
        }
    }

    public function testError(): void
    {
        if ($this->api->getErrorMessage(404, [],

                // API json response
                json_encode(["message" => "test"])) !== "404 | test") {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }

        if (str_starts_with($this->api->getErrorMessage(500, [],

                // can not parse
                // server whatever response
                // try debug log
                ""), "500 |") === false) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testReferencesUrl(): void
    {
        // path has leading slash
        if ($this->api->getReferencesUrl("/valvoid/fusion") !==
            $this->config["url"] .

            // as it is
            "/valvoid/fusion/tags?per_page=100") {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testReferences(): void
    {
        $content = ["ref"];
        $references = $this->api->getReferences("", [
            'link: <https://github.com>; rel="next"',

            // array of objects
            // ballast since need only name
        ], [["name" => "ref"]]);

        // pagination
        // asset next api link to get more references
        if ($references->getUrl() !== "https://github.com" ||
            $references->getEntries() !== $content) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " .

                // multi test pointer
                __LINE__;

            $this->result = false;
        }

        // last page or
        // next link is optional
        // no pagination
        $references = $this->api->getReferences("", [],

            // array of objects
            // ballast since need only name
            [["name" => "ref"]]);

        if ($references->getUrl() !== null ||
            $references->getEntries() !== $content) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " .

                // multi test pointer
                __LINE__;

            $this->result = false;
        }
    }
}