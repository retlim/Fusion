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

namespace Valvoid\Fusion\Log\Serializers\Files;

use Valvoid\Fusion\Log\Events\Event;
use Valvoid\Fusion\Log\Events\Level;

/**
 * File serializer.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
interface File
{
    /**
     * Constructs the file serializer.
     *
     * @param array $config Config.
     */
    public function __construct(array $config);

    /**
     * Logs event.
     *
     * @param Event|string $event Event.
     * @param Level $level Level.
     */
    public function log(Level $level, Event|string $event): void;
}