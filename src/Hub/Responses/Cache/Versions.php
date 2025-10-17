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

namespace Valvoid\Fusion\Hub\Responses\Cache;

/**
 * Versions response.
 */
class Versions extends Cache
{
    /** @var string[] Entries. */
    private array $entries;

    /**
     * Constructs the response.
     *
     * @param int $id Unique request ID.
     * @param string[] $entries Entries.
     */
    public function __construct(int $id, array $entries)
    {
        parent::__construct($id);

        $this->entries = $entries;
    }

    /**
     * Returns inline entries in descending order.
     *
     * @return string[] Entries.
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * Returns the highest entry.
     *
     * @return string Version.
     */
    public function getTopEntry(): string
    {
        return $this->entries[0];
    }
}