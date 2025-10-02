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

namespace Valvoid\Fusion\Tests\Tasks\Stack\Mocks;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Proxy;
use Valvoid\Fusion\Log\Events\Infos\Content;

/**
 * Mocked container.
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class BoxMock extends Box
{
    public BusMock $bus;

    public function get(string $class, ...$args): object
    {
        if ($class === Proxy::class)
            return $this->bus;

        if ($class === Content::class)
            return new ContentMock;

        return parent::get($class, ...$args);
    }
}