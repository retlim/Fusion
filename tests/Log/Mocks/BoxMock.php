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

namespace Valvoid\Fusion\Tests\Log\Mocks;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Log\Events\Event;
use Valvoid\Fusion\Log\Events\Interceptor;
use Valvoid\Fusion\Log\Proxy;

/**
 * Mocked container.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class BoxMock extends Box
{
    public Proxy $log;
    public function get(string $class, ...$args): object
    {
        return $this->log ??= new class implements Proxy
        {
            public $calls = [];

            public function addInterceptor(Interceptor $interceptor): void
            {
                $this->calls[] = __FUNCTION__;
            }

            public function removeInterceptor(): void
            {
                $this->calls[] = __FUNCTION__;
            }

            public function error(string|Event $event): void
            {
                $this->calls[] = __FUNCTION__;
            }

            public function warning(string|Event $event): void
            {
                $this->calls[] = __FUNCTION__;
            }

            public function notice(string|Event $event): void
            {
                $this->calls[] = __FUNCTION__;
            }

            public function info(string|Event $event): void
            {
                $this->calls[] = __FUNCTION__;
            }

            public function verbose(string|Event $event): void
            {
                $this->calls[] = __FUNCTION__;
            }

            public function debug(string|Event $event): void
            {
                $this->calls[] = __FUNCTION__;
            }
        };
    }

}