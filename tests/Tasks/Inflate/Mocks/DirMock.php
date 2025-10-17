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

namespace Valvoid\Fusion\Tests\Tasks\Inflate\Mocks;

use Closure;
use Valvoid\Fusion\Wrappers\Dir;

class DirMock extends Dir
{
    public Closure $filenames;
    public Closure $is;
    public Closure $rename;

    public function getFilenames(string $dir, int $order = SCANDIR_SORT_ASCENDING): array|false
    {
        return call_user_func($this->filenames, $dir, $order);
    }

    public function is(string $dir): bool
    {
        return call_user_func($this->is, $dir);
    }

    public function rename(string $from, string $to): bool
    {
        return call_user_func($this->rename, $from, $to);
    }
}