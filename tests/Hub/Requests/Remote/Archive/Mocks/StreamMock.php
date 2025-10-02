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

namespace Valvoid\Fusion\Tests\Hub\Requests\Remote\Archive\Mocks;

use Valvoid\Fusion\Wrappers\Stream;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class StreamMock extends Stream
{
    public function __construct() {}
    public function get(): mixed
    {
        return null;
    }

    public function getContents(): string|false
    {
        return "";
    }

    public function rewind(): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }
}