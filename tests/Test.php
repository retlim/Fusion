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

namespace Valvoid\Fusion\Tests;

/**
 * Test case.
 *
 * @Copyright Valvoid
 * @license GNU GPLv3
 */
abstract class Test
{
    /** @var string|array<string> Code coverage. */
    protected string|array $coverage;

    /** @var bool Test result. */
    protected bool $result = true;

    /**
     * Returns the test result. True for success and false for not.
     *
     * @return bool Result.
     */
    public function getResult(): bool
    {
        return $this->result;
    }

    /**
     * Returns optional code coverage.
     *
     * @return null|array|string Class/function names.
     */
    public function getCoverage(): null|array|string
    {
        return $this->coverage ??
            null;
    }
}