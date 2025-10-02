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

namespace Valvoid\Fusion\Tests\Tasks\Shift\Mocks;

use Closure;
use Valvoid\Fusion\Metadata\Internal\Internal;
use Valvoid\Fusion\Metadata\Internal\Category;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class InternalMetadataMock extends Internal
{
    public Closure $delete;
    public Closure $update;

    public function __construct(
        public Category $category,
        public array $content){}

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function getStructureCache(): string
    {
        return$this->content["structure"]["cache"];
    }

    public function getId(): string
    {
        return $this->content["id"];
    }

    public function onDelete(): bool
    {
        call_user_func($this->delete);
        return true;
    }

    public function onUpdate(): bool
    {
        call_user_func($this->update);
        return true;
    }
}