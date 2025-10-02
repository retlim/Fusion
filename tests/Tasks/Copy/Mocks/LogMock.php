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

namespace Valvoid\Fusion\Tests\Tasks\Copy\Mocks;

use Valvoid\Fusion\Log\Events\Event;
use Valvoid\Fusion\Log\Events\Interceptor;
use Valvoid\Fusion\Log\Proxy;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class LogMock implements Proxy
{
    public function addInterceptor(Interceptor $interceptor): void {}
    public function removeInterceptor(): void {}
    public function error(string|Event $event): void {}
    public function warning(string|Event $event): void {}
    public function notice(string|Event $event): void {}
    public function info(string|Event $event): void {}
    public function verbose(string|Event $event): void {}
    public function debug(string|Event $event): void {}
}