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

use Closure;
use Valvoid\Fusion\Log\Events\Event;
use Valvoid\Fusion\Log\Log;

class LogMock extends Log
{
    public static Closure|null $verbose;
    public static Closure|null $debug;

    public static function verbose(string|Event $event): void {
        call_user_func(self::$verbose, $event);
    }
    public static function debug(string|Event $event): void
    {
        call_user_func(self::$debug, $event);
    }
}