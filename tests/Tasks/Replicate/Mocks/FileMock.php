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

namespace Valvoid\Fusion\Tests\Tasks\Replicate\Mocks;

use Closure;
use Valvoid\Fusion\Wrappers\File;

/**
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class FileMock extends File
{
    public Closure $put;
    public Closure $get;
    public Closure $exists;

    public function put(string $file, mixed $data): int|false
    {
        return call_user_func($this->put, $file, $data);
    }

    public function get(string $file): string|false
    {
        return call_user_func($this->get, $file);
    }

    public function exists(string $file): bool
    {
        return call_user_func($this->exists, $file);
    }

}