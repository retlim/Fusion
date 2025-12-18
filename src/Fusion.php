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

namespace Valvoid\Fusion;

use Exception;
use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Events\Root;
use Valvoid\Fusion\Bus\Logic as BusLogic;
use Valvoid\Fusion\Bus\Proxy as BusProxy;
use Valvoid\Fusion\Config\Logic as ConfigLogic;
use Valvoid\Fusion\Config\Proxy as ConfigProxy;
use Valvoid\Fusion\Dir\Dir as Directory;
use Valvoid\Fusion\Group\Group as GroupProxy;
use Valvoid\Fusion\Group\Logic as GroupLogic;
use Valvoid\Fusion\Hub\Hub;
use Valvoid\Fusion\Log\Events\Errors\Config as ConfigError;
use Valvoid\Fusion\Log\Events\Errors\Error as InternalError;
use Valvoid\Fusion\Log\Events\Event as LogEvent;
use Valvoid\Fusion\Log\Events\Infos\Name;
use Valvoid\Fusion\Log\Events\Interceptor;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Tasks\Task;
use Valvoid\Fusion\Wrappers\Dir;
use Valvoid\Fusion\Wrappers\File;

/**
 * Package manager for PHP-based projects.
 */
class Fusion
{
    /** @var string Variation, nested, and raw root directory. */
    private string $root;

    /** @var array Namespace prefixes to path. */
    private array $prefixes;

    /**
     * Constructs the package manager.
     *
     * @param Box $box Dependency injection container.
     * @param File $file Wrapper for standard file operations.
     * @param Dir $dir Wrapper for standard directory operations.
     * @param array $config Runtime config layer.
     * @throws ConfigError Invalid config exception.
     * @throws InternalError Internal error.
     * @throws Exception Internal error.
     */
    public function __construct(
        private readonly Box $box,
        private readonly File $file,
        private readonly Dir $dir,
        array $config = [])
    {
        spl_autoload_register($this->loadLazyCode(...),

            // high priority
            prepend: true);

        // set up proxies
        $box->map(BusLogic::class, BusProxy::class);
        $box->map(ConfigLogic::class, ConfigProxy::class);
        $box->map(GroupLogic::class, GroupProxy::class);

        // shareable objects
        $box->recycle(BusLogic::class,
            Log::class,
            ConfigLogic::class,
            GroupLogic::class,
            Directory::class,
            Hub::class);

        $root = $dir->getDirname(__DIR__);
        $this->root = $this->getRoot($root);
        $prefixes = "$root/state/prefixes.php";
        $overlay = $config["persistence"]["overlay"] ??
            true;

        if ($overlay && $file->is($prefixes))
            $this->prefixes = $file->require($prefixes);

        // fallback, raw prefix
        // to fix broken state for example
        else $this->prefixes = [
            __NAMESPACE__ => substr($root, strlen($this->root)) . "/src"
        ];

        $bus = $box->get(BusLogic::class);
        $config = $box->get(ConfigLogic::class,
            root: $root,
            path: $this->root,
            prefixes: $this->prefixes,
            config: $config);

        $config->load($overlay);
        $bus->addReceiver(self::class, $this->handleBusEvent(...),

            // keep session active if
            // recursive or nested update/upgrade
            Root::class);
    }

    /**
     * Destroys the package manager.
     */
    public function __destruct()
    {
        spl_autoload_unregister($this->loadLazyCode(...));
    }

    /**
     * Loads lazy loadable.
     *
     * @param string $loadable Loadable.
     */
    private function loadLazyCode(string $loadable): void
    {
        foreach ($this->prefixes as $prefix => $path)
            if (str_starts_with($loadable, $prefix)) {
                $suffix = substr($loadable, strlen($prefix));
                $suffix = str_replace('\\', '/', $suffix);
                $file = $this->root . "$path$suffix.php";

                if ($this->file->is($file)) {
                    require $file;
                    break;
                }
            }
    }

    /**
     * Executes a task or task group and manages any resulting
     * project changes.
     *
     * @param string $id The ID of the task or task group to execute.
     * @return bool True on success, false on failure.
     * @throws Exception
     */
    public function execute(string $id): bool
    {
        $log = $this->box->get(Log::class);

        try {
            $entry = $this->box->get(ConfigLogic::class)
                ->get("tasks", $id) ??
                    throw new InternalError(
                        "Task id '$id' does not exist."
                    );

            // drop previous state ballast
            $this->normalize();

            /** @var Task $task */
            if (isset($entry["task"])) {
                $task = $this->box->get($entry["task"],
                    config: $entry);

                if (is_subclass_of($task, Interceptor::class)) {
                    $log->addInterceptor($task);
                    $task->execute();
                    $log->removeInterceptor();

                } else $task->execute();

            } else foreach ($entry as $taskId => $task) {
                $log->info($this->box->get(Name::class,
                    name: $taskId));

                $task = $this->box->get($task["task"],
                    config: $task);

                if (is_subclass_of($task, Interceptor::class)) {
                    $log->addInterceptor($task);
                    $task->execute();
                    $log->removeInterceptor();

                } else $task->execute();
            }

        } catch (LogEvent $error) {
            $log->error($error);
        }

        return !isset($error);
    }

    /**
     * Normalizes working directory.
     *
     * @throws InternalError Internal error.
     * @throws Exception
     */
    private function normalize(): void
    {
        $dir = $this->box->get(Directory::class);

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

    /**
     * Returns non-nested root.
     *
     * @param string $path Directory to start.
     * @return string Root.
     * @throws InternalError
     */
    private function getRoot(string $path): string
    {
        $match = null;

        while ($path) {
            if ($this->file->is("$path/fusion.json"))
                $match = $path;

            $parent = $this->dir->getDirname($path);

            if ($path == $parent)
                break;

            $path = $parent;
        }

        if ($match === null)
            throw new InternalError(
                "Cant get root path."
            );

        return $match;
    }
}