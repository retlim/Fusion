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

namespace Valvoid\Fusion\Tests\Metadata\External\Mocks;

use Valvoid\Fusion\Metadata\Internal\Internal as InternalMeta;
use Valvoid\Fusion\Tasks\Group;

class GroupMock extends Group
{
    public function getInternalMetas(): array
    {
        return [
            "identifier" => new class extends InternalMeta
            {
                public function __construct() {}

                public function getId(): string
                {
                    return "id";
                }

                public function getSource(): string
                {
                    return "--dir--";
                }

                public function getVersion(): string
                {
                    return "--version--";
                }
            }
        ];
    }
}