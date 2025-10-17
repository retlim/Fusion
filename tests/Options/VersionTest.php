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

namespace Valvoid\Fusion\Tests\Options;

use Throwable;
use Valvoid\Fusion\Options\Version;
use Valvoid\Fusion\Tests\Options\Mocks\FileMock;
use Valvoid\Fusion\Tests\Test;

class VersionTest extends Test
{
    protected string|array $coverage = Version::class;

    public function __construct()
    {
        $this->testSemanticVersion();
    }

    public function testSemanticVersion(): void
    {
        try {
            $file = new FileMock;
            $file->get = function () {
                return "{\"version\": \"###\"}";
            };

            $version = new Version($file);

            if ($version->semver !== "###")
                $this->handleFailedTest();

        } catch (Throwable) {
            $this->handleFailedTest();
        }
    }
}