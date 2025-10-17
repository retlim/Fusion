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

namespace Valvoid\Fusion\Settings;

use Exception;
use Valvoid\Fusion\Wrappers\Dir;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Fusion\Wrappers\System;

/**
 * Command to interact with persisted config.
 */
class Config
{
    /** @var string Absolute config file. */
    public readonly string $file;

    /**
     * Constructs the command.
     *
     * @param Dir $dir Wrapper for standard directory operations.
     * @param File $fileWrapper Wrapper for standard file operations.
     * @param System $system Wrapper for standard system operations.
     * @throws Exception
     */
    public function __construct(
        private readonly Dir $dir,
        private readonly File $fileWrapper,
        private readonly System $system)
    {
        $root = $this->dir->getDirname(__DIR__, 2);
        $path = $this->getNonNestedPath($root);

        // custom, nested variation
        // extract identifier from production metadata
        if ($root != $path) {
            $fileWrapper = "$path/fusion.json";
            $metadata = $this->fileWrapper->get($fileWrapper);

            if ($metadata === false)
                throw new Exception(
                    "Cant read root metadata '$fileWrapper'."
                );

            $metadata = json_decode($metadata, true);

            if ($metadata === null)
                throw new Exception(
                    "Cant decode root metadata '$fileWrapper'."
                );

            $identifier = $metadata["id"] ??
                throw new Exception(
                    "Invalid root metadata '$fileWrapper'. " .
                    "Cant extract package identifier."
                );

        // default variation
        // do not extract
        } else $identifier = "valvoid/fusion";

        if ($system->getOsFamily() == 'Windows') {
            $path = $this->system->getEnvVariable('LOCALAPPDATA') ?:
                $this->system->getEnvVariable('USERPROFILE') . '/AppData/Local';

            $identifier = ucwords($identifier, '/');
            $this->file = "$path/$identifier/config/config.json";

        } else {
            $path = $this->system->getEnvVariable('XDG_CONFIG_HOME') ?:
                $this->system->getEnvVariable('HOME') . "/.config";

            $this->file = "$path/$identifier/config.json";
        }
    }

    /**
     * Returns path to top package directory.
     *
     * @param string $path Dir to start the lookup.
     * @return string Path.
     * @throws Exception
     */
    private function getNonNestedPath(string $path): string
    {
        $match = null;

        while ($path) {
            if ($this->fileWrapper->is("$path/fusion.json"))
                $match = $path;

            $parent = $this->dir->getDirname($path);

            if ($path == $parent)
                break;

            $path = $parent;
        }

        return $match ?? throw new Exception(
            "Cant read path '$path'."
        );
    }

    /**
     * Persists config.
     *
     * @param array $arguments Entries.
     * @throws Exception
     */
    public function persist(array $arguments): void
    {
        $content = $this->getContent();

        if ($content != null) {
            $content = json_decode($content, true);

            if ($content === null)
                throw new Exception(
                    "Cant decode persisted config. " .
                    json_last_error_msg()
                );

            $this->overlay($content, $arguments);

        } else $content = $arguments;

        $content = json_encode($content, JSON_PRETTY_PRINT|
            JSON_UNESCAPED_SLASHES);

        if ($content === false)
            throw new Exception(
                "Cant encode config. " .
                json_last_error_msg()
            );

        if (false === $this->fileWrapper->put($this->file, $content))
            throw new Exception(
                "Cant write to '$this->file'."
            );
    }

    /**
     * Returns config if exists.
     *
     * @return string|null
     * @throws Exception
     */
    public function getContent(): string|null
    {
        if (!$this->fileWrapper->exists($this->file))
            return null;

        $content = $this->fileWrapper->get($this->file);

        if ($content === false)
            throw new Exception(
                "Cant read file '$this->file'."
            );

        return $content;
    }

    /**
     * Overlays composite config.
     *
     * @param array $config Composite config.
     * @param array $layer On top config.
     */
    private function overlay(array &$config, array $layer): void
    {
        foreach ($layer as $key => $value)
            if ($value === null)
                $config[$key] = $value;

            elseif (is_array($value)) {

                // init shell for one to many add rule
                if (!isset($config[$key]) || !is_array($config[$key]))
                    $config[$key] = [];

                $this->overlay($config[$key], $value);

            // extend with seq value if not exist
            // one to many add rule
            } elseif (isset($config[$key]) && is_array($config[$key])) {
                if (!in_array($value, $config[$key]))
                    $config[$key][] = $value;

            // override or set
            // one to one add rule
            } else $config[$key] = $value;
    }
}