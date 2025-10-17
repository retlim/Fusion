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

namespace Valvoid\Fusion\Tests\Hub\Mocks;

use Valvoid\Fusion\Hub\APIs\Local\Local;
use Valvoid\Fusion\Hub\APIs\Remote\Remote;
use Valvoid\Fusion\Hub\Cache;

class CacheMock extends Cache
{
    public string $root;

    public function __construct(string $root = "")
    {
        $this->root = $root;
    }

    public function getLocalDir(array $source): string
    {
        return $this->root;
    }

    public function getReferencesState(array $source): bool|int
    {
        return true;
    }

    public function getFileState(array $source, string $filename, Local|Remote $api): bool|int
    {
        return true;
    }

    public function getVersions(string $api, string $path, array $reference): array
    {
        return [
            "1.3.4",
            "1.2.3"
        ];
    }
}