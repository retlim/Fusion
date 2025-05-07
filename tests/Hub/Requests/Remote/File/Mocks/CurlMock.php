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

namespace Valvoid\Fusion\Tests\Hub\Requests\Remote\File\Mocks;

use Valvoid\Fusion\Hub\Requests\Remote\Wrappers\Curl;

/**
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class CurlMock extends Curl
{
    public int $code = 0;
    public mixed $optionValue = "";

    public function getInfo(?int $option): int
    {
        return $this->code;
    }

    public function setOption(int $option, mixed $value): bool
    {
        $this->optionValue = $value;

        return true;
    }
}