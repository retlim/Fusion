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

namespace Valvoid\Fusion;

use Exception;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Root;
use Valvoid\Fusion\Bus\Events\Boot;
use Valvoid\Fusion\Container\Container;
use Valvoid\Fusion\Container\Proxy\Logic;
use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Config\Config;
use Valvoid\Fusion\Log\Events\Errors\Config as ConfigError;
use Valvoid\Fusion\Log\Events\Errors\Error as InternalError;
use Valvoid\Fusion\Log\Events\Errors\Metadata as MetadataError;
use Valvoid\Fusion\Log\Events\Event as LogEvent;
use Valvoid\Fusion\Log\Events\Infos\Id;
use Valvoid\Fusion\Log\Events\Infos\Name;
use Valvoid\Fusion\Log\Events\Interceptor;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Log\Proxy\Proxy as LogProxy;
use Valvoid\Fusion\Log\Proxy\Logic as LogLogic;
use Valvoid\Fusion\Tasks\Task;
use Valvoid\Fusion\Hub\Proxy\Proxy as HubProxy;
use Valvoid\Fusion\Hub\Proxy\Logic as HubLogic;
use Valvoid\Fusion\Group\Proxy\Proxy as GroupProxy;
use Valvoid\Fusion\Group\Proxy\Logic as GroupLogic;
use Valvoid\Fusion\Bus\Proxy\Proxy as BusProxy;
use Valvoid\Fusion\Bus\Proxy\Logic as BusLogic;
use Valvoid\Fusion\Dir\Proxy\Proxy as DirProxy;
use Valvoid\Fusion\Dir\Proxy\Logic as DirLogic;
use Valvoid\Fusion\Config\Proxy\Proxy as ConfigProxy;
use Valvoid\Fusion\Config\Proxy\Logic as ConfigLogic;

/**
 * Package manager for PHP-based projects.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Fusion
{
    /** @var ?Fusion Runtime instance. */
    private static ?Fusion $instance = null;

    /** @var string Source root directory. */
    private string $root;

    /** @var bool Lock indicator. */
    private bool $busy = false;

    /** @var array Lazy code registry. */
    private array $lazy;

    /**
     * Constructs the package manager.
     *
     * @param array $config Runtime config layer.
     * @throws ConfigError Invalid config exception.
     * @throws MetadataError Invalid metadata exception.
     * @throws InternalError Internal error.
     */
    private function __construct(array $config)
    {
        $this->root = dirname(__DIR__);
        $this->lazy = require "$this->root/cache/loadable/lazy.php";

        spl_autoload_register($this->loadLazyLoadable(...));

        // set up proxies
        (new Logic)->get(Container::class);
        Container::refer(BusProxy::class, BusLogic::class);
        Container::refer(LogProxy::class, LogLogic::class);
        Container::refer(ConfigProxy::class, ConfigLogic::class);
        Container::refer(GroupProxy::class, GroupLogic::class);
        Container::refer(DirProxy::class, DirLogic::class);
        Container::refer(HubProxy::class, HubLogic::class);

        // init sharable config instance
        Container::get(ConfigProxy::class,
            root: $this->root,
            lazy: $this->lazy,
            config: $config
        );

        // trigger lazy config build due to self reference
        Bus::broadcast(new Boot);
        Bus::addReceiver(self::class, $this->handleBusEvent(...),

            // keep session active if
            // recursive or nested update/upgrade
            Root::class);
    }

    /**
     * Initializes the package manager instance.
     *
     * @param array $config Runtime config layer.
     * @return bool True for success. False for has destroyable instance.
     * @throws ConfigError Invalid config exception.
     * @throws MetadataError Invalid metadata exception.
     * @throws InternalError Internal error.
     */
    public static function init(array $config = []): bool
    {
        if (self::$instance !== null)
            return false;

        self::$instance = new self($config);

        return true;
    }

    /**
     * Loads lazy loadable.
     *
     * @param string $loadable Loadable.
     */
    private function loadLazyLoadable(string $loadable): void
    {
        // registered
        // hide unregistered warning
        // show custom error
        if (@$file = $this->lazy[$loadable])
            require $this->root . $file;
    }

    /**
     * Destroys the package manager instance.
     *
     * @return bool True for success.
     * @throws InternalError Internal error.
     */
    public static function destroy(): bool
    {
        $fusion = &self::$instance;

        if ($fusion === null)
            return true;

        if ($fusion->busy)
            return false;

        Bus::removeReceiver(self::class);
        Container::unset(Container::class);

        $fusion = null;

        return true;
    }

    /**
     * Manages project changes generated by task or task
     * group execution.
     *
     * @param string $id Callable task or group ID.
     * @throws Exception Destroyed object exception.
     * @return bool True for success.
     */
    public static function manage(string $id): bool
    {
        $fusion = self::$instance;

        if ($fusion === null || $fusion->busy)
            return false;

        $fusion->busy = true;

        try {
            $entry = Config::get("tasks", $id) ??
                throw new InternalError(
                    "Task id \"$id\" does not exist."
                );

            Log::info(new Id($id));
            $fusion->normalize();

            /** @var Task $task */
            if (isset($entry["task"])) {
                $task = new $entry["task"]($entry);

                if (is_subclass_of($task, Interceptor::class)) {
                    Log::addInterceptor($task);
                    $task->execute();
                    Log::removeInterceptor();

                } else
                    $task->execute();

            } else {
                foreach ($entry as $taskId => $task) {
                    Log::info(new Name($taskId));

                    $task = new $task["task"]($task);

                    if (is_subclass_of($task, Interceptor::class)) {
                        Log::addInterceptor($task);
                        $task->execute();
                        Log::removeInterceptor();

                    } else
                        $task->execute();
                }
            }

        } catch (LogEvent $error) {
            Log::error($error);
        }

        $fusion->normalize();
        $fusion->busy = false;

        return !isset($error);
    }

    /**
     * Normalizes working directory.
     *
     * @throws InternalError Internal error.
     */
    protected function normalize(): void
    {
        Dir::delete(Dir::getStateDir());
        Dir::delete(Dir::getTaskDir());
        Dir::delete(Dir::getPackagesDir());
        Dir::delete(Dir::getOtherDir());
    }

    /**
     * Handles bus event.
     *
     * @param Root $event Root event.
     */
    private function handleBusEvent(Root $event): void
    {
        $this->root = $event->getDir();
    }
}