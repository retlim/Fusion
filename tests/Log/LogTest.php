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

namespace Valvoid\Fusion\Tests\Log;

use Valvoid\Fusion\Log\Events\Errors\Config;
use Valvoid\Fusion\Log\Events\Errors\Deadlock;
use Valvoid\Fusion\Log\Events\Errors\Environment;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Errors\Lifecycle;
use Valvoid\Fusion\Log\Events\Errors\Metadata;
use Valvoid\Fusion\Log\Events\Errors\Request;
use Valvoid\Fusion\Log\Events\Infos\Content;
use Valvoid\Fusion\Log\Events\Infos\Error as InfoError;
use Valvoid\Fusion\Log\Events\Infos\Id;
use Valvoid\Fusion\Log\Events\Infos\Name;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Tests\Log\Mocks\ContainerMock;
use Valvoid\Fusion\Tests\Log\Mocks\InterceptorMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Log test.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class LogTest extends Test
{
    protected string|array $coverage = [
        Log::class,

        // ballast
        Name::class,
        Id::class,
        InfoError::class,
        Content::class,
        Config::class,
        Deadlock::class,
        Environment::class,
        Error::class,
        Lifecycle::class,
        Metadata::class,
        Request::class
    ];

    private ContainerMock $container;
    private InterceptorMock $interceptor;

    public function __construct()
    {
        $this->container = new ContainerMock;
        $this->interceptor = new InterceptorMock;

        // static
        $this->testStaticInterface();
        $this->container->destroy();
    }

    public function testStaticInterface(): void
    {
        Log::removeInterceptor();
        Log::addInterceptor($this->interceptor);
        Log::debug("");
        Log::error("");
        Log::warning("");
        Log::notice("");
        Log::info("");
        Log::debug("");

        // static functions connected to same non-static functions
        if ($this->container->logic->log->calls !== [
                "removeInterceptor",
                "addInterceptor",
                "debug",
                "error",
                "warning",
                "notice",
                "info",
                "debug"]) {

            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}