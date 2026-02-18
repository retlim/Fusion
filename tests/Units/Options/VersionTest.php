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

namespace Valvoid\Fusion\Tests\Units\Options;

use Valvoid\Fusion\Options\Version;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Reflex\Test\Wrapper;

class VersionTest extends Wrapper
{
    public function testSemanticVersion(): void
    {
        $filename = dirname(__DIR__, 3) . "/fusion.json";
        $file = $this->createMock(File::class);

        $file->fake("get")
            ->expect(file: $filename)
            ->return('{"version": "###"}');

        $version = new Version($file);

        $this->validate($version->semver)
            ->as("###");
    }
}