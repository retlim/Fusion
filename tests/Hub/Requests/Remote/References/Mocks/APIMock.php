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

namespace Valvoid\Fusion\Tests\Hub\Requests\Remote\References\Mocks;

use Valvoid\Fusion\Hub\APIs\Remote\Remote;
use Valvoid\Fusion\Hub\APIs\Remote\Status;
use Valvoid\Fusion\Hub\Responses\Remote\References;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class APIMock extends Remote
{
    public string $invalidToken = "";

    // two request
    // first has next url
    // second is done
    public array $next = [
        "sdsdfsd"
    ];

    public string $prefix = "";

    public function __construct() {}

    public function getTokens(string $path): array
    {
        return ["0", "1"];
    }

    public function getReferencesUrl(string $path): string
    {
        return "api$path/references";
    }

    public function getReferencesOptions(): array
    {
        return [];
    }

    public function addInvalidToken(string $token): bool
    {
        $this->invalidToken = $token;

        return true;
    }


    public function getStatus(int $code, array $headers): Status
    {
        return match ($code) {
            200 => Status::OK,
            401 => Status::UNAUTHORIZED,
            403 => Status::FORBIDDEN,
            404 => Status::NOT_FOUND,
            429 => Status::TO_MANY_REQUESTS,
            default => Status::ERROR
        };
    }

    public function getReferences(string $path, array $headers, array $content): References
    {
        $next = array_shift($this->next);

        return new References(
            ($next === null) ? [
                $this->prefix . "1.0.0",
                $this->prefix . "3.4.5",

                // api may return whatever tags
                $this->prefix . "whatever"

            ] : [
                $this->prefix . "4.5.6",
                $this->prefix . "456"

            ], $next
        );
    }

    public function getRateLimitReset(array $headers, string $content): int
    {
        return time();
    }

    public function getErrorMessage(int $code, array $headers, string $content): string
    {
        return "";
    }

    public function getFileUrl(string $path, string $reference, string $file): string {return "";}
    public function getFileOptions(): array {return [];}
    public function getArchiveUrl(string $path, string $reference): string {return "";}
    public function getArchiveOptions(): array {return [];}
}