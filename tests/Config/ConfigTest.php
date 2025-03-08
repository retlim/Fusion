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

namespace Valvoid\Fusion\Tests\Config;

use Exception;
use Valvoid\Fusion\Container\Proxy\Logic as ContainerLogic;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Config\Config;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Tests\Config\Mocks\ContainerMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Config test.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ConfigTest extends Test
{
    protected string|array $coverage = Config::class;

    private string $root;

    private array $lazy;

    private Config $config;

    /**
     */
    public function __construct()
    {
        try {
            $containerMock = new ContainerMock;
            $this->root = dirname(__DIR__, 2);
            $this->lazy = require "$this->root/cache/loadable/lazy.php";

            $this->config = (new ContainerLogic)->get(Config::class,
                root: $this->root,
                lazy: $this->lazy,
                config: []);

            $this->testInstanceDestruction();

            (new ContainerLogic)->unset(Config::class);
            $containerMock->destroy();

        } catch (Exception $exception) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    /**
     * @return void
     * @throws Error
     */
    public function testInstanceDestruction(): void
    {
        $instance = $this->config;
        (new ContainerLogic)->unset(Config::class);
        $this->config = (new ContainerLogic)->get(Config::class,
            root: $this->root,
            lazy: $this->lazy,
            config: []);

        // assert different instances
        if ($instance === $this->config) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}