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

namespace Valvoid\Fusion\Options;

use Exception;
use Valvoid\Fusion\Wrappers\Dir;
use Valvoid\Fusion\Wrappers\File;
use Valvoid\Fusion\Wrappers\System;

/**
 * Flag option to get config, cache, and state paths.
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class Paths
{
    /** @var string  Source code path. */
    public readonly string $path;

    /** @var string  Cache path. */
    public readonly string $cache;

    /** @var string  Config path. */
    public readonly string $config;

    /** @var string State path. */
    public readonly string $state;

    /**
     * Constructs the option.
     *
     * @param Dir $dir Wrapper for standard directory operations.
     * @param File $file Wrapper for standard file operations.
     * @param System $system Wrapper for standard system operations.
     * @throws Exception
     */
    public function __construct(
        private readonly Dir $dir,
        private readonly File $file,
        System $system)
    {
        $root = $this->dir->getDirname(__DIR__, 2);
        $this->path = $this->getNonNestedPath($root);

        // custom, nested variation
        // extract identifier from production metadata
        if ($root != $this->path) {
            $file = "$this->path/fusion.json";
            $metadata = $this->file->get($file);

            if ($metadata === false)
                throw new Exception(
                    "Cant read root metadata '$file'."
                );

            $metadata = json_decode($metadata, true);

            if ($metadata === null)
                throw new Exception(
                    "Cant decode root metadata '$file'."
                );

            $identifier = $metadata["id"] ??
                throw new Exception(
                    "Invalid root metadata '$file'. " .
                    "Cant extract package identifier."
                );

        // default variation
        // do not extract
        } else $identifier = "valvoid/fusion";

        if ($system->getOsFamily() == 'Windows') {
            $localAppData = $system->getEnvVariable('LOCALAPPDATA')
                ?: ($system->getEnvVariable('USERPROFILE') . '/AppData/Local');

            $identifier = ucwords($identifier, '/');
            $this->cache = "$localAppData/$identifier/cache";
            $this->config = "$localAppData/$identifier/config";
            $this->state = "$localAppData/$identifier/state";

        } else {
            $home = $system->getEnvVariable('HOME');
            $cache = $system->getEnvVariable('XDG_CACHE_HOME') ?: "$home/.cache";
            $config  = $system->getEnvVariable('XDG_CONFIG_HOME') ?: "$home/.config";
            $state = $system->getEnvVariable('XDG_STATE_HOME') ?: "$home/.local/state";
            $this->cache = "$cache/$identifier";
            $this->config = "$config/$identifier";
            $this->state = "$state/$identifier";
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
            if ($this->file->is("$path/fusion.json"))
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
}