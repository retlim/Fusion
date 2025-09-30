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

namespace Valvoid\Fusion;

use Exception;
use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Boot;
use Valvoid\Fusion\Bus\Events\Root;
use Valvoid\Fusion\Bus\Proxy\Logic as BusLogic;
use Valvoid\Fusion\Bus\Proxy\Proxy as BusProxy;
use Valvoid\Fusion\Config\Proxy\Logic as ConfigLogic;
use Valvoid\Fusion\Config\Proxy\Proxy as ConfigProxy;
use Valvoid\Fusion\Dir\Logic as DirLogic;
use Valvoid\Fusion\Dir\Proxy as DirProxy;
use Valvoid\Fusion\Group\Group as GroupProxy;
use Valvoid\Fusion\Group\Logic as GroupLogic;
use Valvoid\Fusion\Hub\Logic as HubLogic;
use Valvoid\Fusion\Hub\Proxy as HubProxy;
use Valvoid\Fusion\Log\Events\Errors\Config as ConfigError;
use Valvoid\Fusion\Log\Events\Errors\Error as InternalError;
use Valvoid\Fusion\Log\Events\Errors\Metadata as MetadataError;
use Valvoid\Fusion\Log\Events\Event as LogEvent;
use Valvoid\Fusion\Log\Events\Infos\Id;
use Valvoid\Fusion\Log\Events\Infos\Name;
use Valvoid\Fusion\Log\Events\Interceptor;
use Valvoid\Fusion\Log\Logic as LogLogic;
use Valvoid\Fusion\Log\Proxy as LogProxy;
use Valvoid\Fusion\Tasks\Task;

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
     * @param Box $box Dependency injection container.
     * @param array $config Runtime config layer.
     * @throws ConfigError Invalid config exception.
     * @throws MetadataError Invalid metadata exception.
     * @throws InternalError Internal error.
     * @throws Exception Internal error.
     */
    private function __construct(
        private readonly Box $box,
        array $config = [])
    {
        $this->root = dirname(__DIR__);
        $this->lazy = require "$this->root/cache/loadable/lazy.php";

        spl_autoload_register($this->loadLazyLoadable(...));

        // set up proxies
        $box->map(BusLogic::class, BusProxy::class);
        $box->map(LogLogic::class, LogProxy::class);
        $box->map(ConfigLogic::class, ConfigProxy::class);
        $box->map(GroupLogic::class, GroupProxy::class);
        $box->map(DirLogic::class, DirProxy::class);
        $box->map(HubLogic::class, HubProxy::class);

        // shareable objects
        $box->recycle(BusLogic::class,
            LogLogic::class,
            ConfigLogic::class,
            GroupLogic::class,
            DirLogic::class,
            HubLogic::class);

        // init sharable config instance
        $config = $box->get(ConfigLogic::class,
            root: $this->root,
            lazy: $this->lazy,
            config: $config);

        $bus = $box->get(BusLogic::class);

        // trigger lazy config build due to self reference
        $bus->broadcast(new Boot);
        $bus->addReceiver(self::class, $this->handleBusEvent(...),

            // keep session active if
            // recursive or nested update/upgrade
            Root::class);

        $box->inject(DirLogic::class,
            config: $config->get("dir"));
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

        require_once __DIR__ . "/Box/Box.php";

        self::$instance = new self(new Box, $config);

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
        Box::unsetInstance();

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
        $log = $fusion->box->get(LogLogic::class);

        try {
            $entry = $fusion->box->get(ConfigLogic::class)->get("tasks", $id) ??
                throw new InternalError(
                    "Task id \"$id\" does not exist."
                );

            $log->info(new Id($id));
            $fusion->normalize();

            /** @var Task $task */
            if (isset($entry["task"])) {
                $task = $fusion->box->get($entry["task"],
                    config: $entry);

                if (is_subclass_of($task, Interceptor::class)) {
                    $log->addInterceptor($task);
                    $task->execute();
                    $log->removeInterceptor();

                } else
                    $task->execute();

            } else {
                foreach ($entry as $taskId => $task) {
                    $log->info(new Name($taskId));

                    $task = $fusion->box->get($task["task"],
                        config: $task);

                    if (is_subclass_of($task, Interceptor::class)) {
                        $log->addInterceptor($task);
                        $task->execute();
                        $log->removeInterceptor();

                    } else
                        $task->execute();
                }
            }

        } catch (LogEvent $error) {
            $log->error($error);
        }

        $fusion->normalize();
        $fusion->busy = false;

        return !isset($error);
    }

    /**
     * Normalizes working directory.
     *
     * @throws InternalError Internal error.
     * @throws Exception
     */
    protected function normalize(): void
    {
        $dir = $this->box->get(DirLogic::class);

        $dir->delete($dir->getStateDir());
        $dir->delete($dir->getTaskDir());
        $dir->delete($dir->getPackagesDir());
        $dir->delete($dir->getOtherDir());
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