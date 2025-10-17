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
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Valvoid\Fusion\Hub\APIs\Local;

use Valvoid\Fusion\Hub\Responses\Local\Offset as OffsetResponse;

/**
 * Local offset API.
 */
abstract class Offset extends Local
{
    /**
     * Returns normalized offset response. A commit ID, sha, hash,
     * whatever unique identifier ...
     *
     * @param string $path Path.
     * @param string $offset Reference offset (commit|branch|...).
     * @return OffsetResponse|string Response or error message.
     */
    abstract public function getOffset(string $path, string $offset): OffsetResponse|string;
}