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

namespace Valvoid\Fusion\Config;

use Exception;
use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Events\Config as ConfigEvent;
use Valvoid\Fusion\Bus\Proxy as BusProxy;
use Valvoid\Fusion\Config\Interpreter\Dir as DirectoryInterpreter;
use Valvoid\Fusion\Config\Interpreter\Interpreter;
use Valvoid\Fusion\Config\Normalizer\Dir as DirectoryNormalizer;
use Valvoid\Fusion\Config\Normalizer\Normalizer;
use Valvoid\Fusion\Config\Parser\Dir as DirectoryParser;
use Valvoid\Fusion\Config\Parser\Parser;
use Valvoid\Fusion\Fusion;
use Valvoid\Fusion\Log\Events\Errors\Config as ConfigError;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Wrappers\Dir;
use Valvoid\Fusion\Wrappers\File;

/**
 * Default config implementation.
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class Logic implements Proxy
{
    /** @var array Separated raw settings. */
    private array $configs;

    /** @var array Compiled settings. */
    private array $content = [];

    /** @var string Current source. */
    private string $layer = "runtime";

    /** @var string Root package ID. */
    private string $identifier;

    /**
     * @var array Lazy code registry.
     * @deprecated Will be removed in version 2.0.0
     */
    private array $lazy = [];

    /**
     * Constructs the config.
     *
     * @param Box $box Dependency injection container.
     * @param string $root Package manager root directory.
     * @param array $prefixes Lazy code registry.
     * @param array $config Runtime config layer.
     * @param Dir $dir Wrapper for standard directory operations.
     * @param File $file Wrapper for standard file operations.
     * @param BusProxy $bus Event bus.
     * @throws Error
     */
    public function __construct(
        private readonly Box $box,
        private readonly string $root,
        private readonly array $prefixes,
        private readonly Dir $dir,
        private readonly File $file,
        private readonly DirectoryParser $dirParser,
        array $config,
        BusProxy $bus)
    {
        $bus->addReceiver(self::class, $this->handleBusEvent(...),

            // parser, interpreter, normalizer
            ConfigEvent::class);

        // support Fusion variations
        // environment may has nested, custom, and default
        // separate files per root package identifier
        $path = $this->dirParser->getRootPath($this->root);
        $this->configs = [
            "runtime" => $config,
            "persistence" => [],
            "default" => []
        ];

        // custom, nested variation
        // extract identifier from production metadata
        if ($this->root != $path) {
            $file = "$path/fusion.json";
            $metadata = $this->file->get($file);

            if ($metadata === false)
                throw new Error(
                    "Cant read root metadata '$file'."
                );

            $metadata = json_decode($metadata, true);

            if ($metadata === null)
                throw new Error(
                    "Cant decode root metadata '$file'."
                );

            $this->identifier = $metadata["id"] ??
                throw new Error(
                    "Invalid root metadata '$file'. " .
                    "Cant extract package identifier."
                );

        // default variation
        // do not extract
        } else $this->identifier = "valvoid/fusion";

        $lazy = "$this->root/state/loadable/lazy.php";

        if ($this->file->exists($lazy))
            $this->lazy = $this->file->require($lazy);
    }

    /**
     * Loads config.
     *
     * @param bool $overlay Load persisted layer indicator.
     * @throws ConfigError Invalid config exception.
     * @throws Error Invalid meta exception.
     * @throws Exception
     */
    public function load(bool $overlay): void
    {
        $config = $this->configs["runtime"];

        // current working directory
        // exceptional entry
        // individually before all others, as they are based on it
        if (isset($config["dir"])) {
            $this->box->get(DirectoryInterpreter::class)
                ->interpret($config);

            if (isset($config["dir"]["path"]))
                $this->dirParser->parse($config);
        }

        // individually before all other entries
        // init default first for reverse content checks
        $this->box->get(DirectoryNormalizer::class)
            ->normalize($config, $this->identifier);

        $this->loadDefaultLayer();

        if ($overlay)
            $this->loadPersistenceLayer($config["config"]["path"]);

        $this->layer = "runtime";

        $this->box->get(Interpreter::class)
            ->interpret($config);

        $this->box->get(Parser::class)
            ->parse($config);

        $this->box->get(Normalizer::class)
            ->overlay($this->content, $config);

        $this->layer = "all config files merged";

        $this->box->get(Normalizer::class)
            ->normalize($this->content);
    }

    /**
     * Loads default config layer.
     *
     * @throws ConfigError|Error Invalid config exception.
     * @throws Exception
     */
    private function loadDefaultLayer(): void
    {
        $this->loadConfigs(
            $this->configs["default"],
            "$this->root/config"
        );

        $this->overlayConfigs($this->configs["default"]);
    }

    /**
     * Overlays configs.
     *
     * @param array $wrapper
     * @throws Exception
     */
    private function overlayConfigs(array $wrapper): void
    {
        foreach (array_reverse($wrapper) as $file => $content) {
            $this->layer = $file;

            // actually null is validatable config
            // but reset manually
            if ($content !== null) {
                $this->box->get(Parser::class)
                    ->parse($content);

                $this->box->get(Normalizer::class)
                    ->overlay($this->content, $content);

            // null
            // reset and keep root
            } else $this->content = [];
        }
    }

    /**
     * Loads configs.
     *
     * @param string $dir Directory.
     * @param array $wrapper
     * @throws ConfigError|Error Invalid config exception.
     */
    private function loadConfigs(array &$wrapper, string $dir): void
    {
        $filenames = $this->dir->getFilenames($dir);

        if ($filenames === false)
            throw new Error(
                "Cant read config directory '$dir'."
            );

        foreach ($filenames as $filename)
            if ($filename != "." && $filename != "..") {
                $file = "$dir/$filename";

                if ($this->dir->is($file))
                    $this->loadConfigs($wrapper, $file);

                elseif (str_ends_with($filename, ".php")) {
                    $this->layer = $file;
                    $config = $this->file->include($file);
                    $wrapper[$file] = $config;

                    if ($config === false)
                        $this->handleBusEvent(new ConfigEvent(
                            "Can't read the file.",
                            Level::ERROR
                        ));

                    $this->box->get(Interpreter::class)
                        ->interpret($config);
                }
            }
    }

    /**
     * Loads persistence config layer.
     *
     * @param string $path
     * @throws Error
     * @throws ConfigError Invalid config exception.
     * @throws Exception
     */
    private function loadPersistenceLayer(string $path): void
    {
        $file = "$this->root/state/extensions.php";
        $extensions = $this->file->require($file);

        if ($extensions === false)
            throw new Error(
                "Cant read config extensions '$file'."
            );

        // flat implication
        // may contain duplicate values
        // take first
        $extensions = array_unique($extensions["/extensions/config"]);
        $system = "$path/config.json";

        foreach ($extensions as $extension) {
            $dir = $extension;

            // mapped directory or
            // deprecated legacy identifier
            if (!$this->dir->is($dir)) {
                $dir = "$this->root/extensions/config/$extension";

                if (!$this->dir->is($dir))
                    throw new Error(
                        "Cant read config extension '$dir'."
                    );
            }

            $this->configs["persistence"][$dir] = [];

            $this->loadConfigs(

                // debug/error trace wrapper
                $this->configs["persistence"][$dir],
                $dir);

            $this->overlayConfigs($this->configs["persistence"]

                // stack per identifier
                [$dir]);
        }

        // persisted root config
        // overrides others
        if ($this->file->exists($system)) {
            $config = $this->file->get($system);

            if ($config === false)
                throw new Error(
                    "Cant read config '$system'."
                );

            $config = json_decode($config, true);

            if ($config === null)
                throw new Error(
                    "Cant decode config '$system'. " .
                    json_last_error_msg()
                );

            $this->box->get(Interpreter::class)
                ->interpret($config);

            $this->overlayConfigs([$config]);
        }
    }

    /**
     * Returns config.
     *
     * @param string ...$breadcrumb Index path inside config.
     * @return mixed Config.
     */
    public function get(string ...$breadcrumb): mixed
    {
        $content = $this->content;

        foreach ($breadcrumb as $index)
            if (isset($content[$index]))
                $content = $content[$index];

            else
                return null;

        return $content;
    }

    /**
     * Returns lazy code registry.
     *
     * @return array Lazy.
     */
    public function getLazy(): array
    {
        return $this->lazy;
    }

    /**
     * Returns indicator for existing lazy code.
     *
     * @param string $class Class.
     * @return bool Indicator.
     */
    public function hasLazy(string $class): bool
    {
        return class_exists($class, true);
    }

    /**
     * Handles bus event.
     *
     * @param ConfigEvent $event Root event.
     * @throws ConfigError|Error Invalid config exception.
     */
    private function handleBusEvent(ConfigEvent $event): void
    {
        $breadcrumb = $event->getBreadcrumb();

        // cli or object interaction
        if ($this->layer == "runtime") {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

            // reverse
            // take first match
            foreach (array_reverse($backtrace) as $entry)
                if ($entry["class"] == Fusion::class) {
                    $this->layer = $entry["file"];
                    break;
                }
        }

        $config = new ConfigError(
            $event->getMessage(),
            $this->layer,
            $breadcrumb
        );

        match ($event->getLevel()) {
            Level::ERROR => throw $config,
            Level::WARNING => Log::warning($config),
            Level::NOTICE => Log::notice($config),
            Level::VERBOSE => Log::verbose($config),
            Level::INFO => Log::info($config)
        };
    }
}