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

namespace Valvoid\Fusion\Tests\Hub\Requests\Local\References\Mocks;

use Valvoid\Fusion\Hub\Cache;

/**
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class CacheMock extends Cache
{
    public array $versions = [];
    public int $lock = -1; // request ID
    public bool $conflict = false;

    public function __construct() {}

    public function lockReferences(array $source, int $id): void
    {
        $this->lock = $id;
    }

    public function unlockReferences(array $source): void
    {
        $this->lock = -1;
    }

    public function addVersion(string $api, string $path, string $inline): bool
    {
        $this->versions[] = $inline;

        return !$this->conflict;
    }
}