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

namespace Valvoid\Fusion\Config\Normalizer;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Events\Config as ConfigEvent;
use Valvoid\Fusion\Config\Parser\Dir as DirectoryParser;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Bus\Proxy as BusProxy;
use Valvoid\Fusion\Wrappers\System;
use Valvoid\Fusion\Wrappers\Dir as DirWrapper;

/**
 * Directories config normalizer.
 */
class Dir
{
    /**
     * Constructs the normalizer.
     *
     * @param Box $box Dependency injection container.
     * @param DirWrapper $dir Wrapper for standard directory operations.
     * @param System $system Wrapper for standard system operations.
     */
    public function __construct(
        private readonly Box $box,
        private readonly DirWrapper $dir,
        private readonly DirectoryParser $parser,
        private readonly BusProxy $bus,
        private readonly System $system) {}

    /**
     * Normalizes the working directory config.
     *
     * @param array $config Config to normalize.
     * @param string $identifier
     * @throws Error
     */
    public function normalize(array &$config, string $identifier): void
    {
        $config["dir"]["creatable"] ??= true;
        $config["dir"]["clearable"] ??= false;

        if (!isset($config["dir"]["path"])) {
            $cwd = $this->dir->getCwd();

            if ($cwd === false)
                $this->bus->broadcast(
                    $this->box->get(ConfigEvent::class,
                        message: "Can't set the default value for the 'path' " .
                        "key. Looks like not all parent directories have readable " .
                        "or search mode set.",
                        level: Level::ERROR,
                        breadcrumb: ["dir", "path"],
                        abstract: []
                    ));

            $config["dir"]["path"] = $this->parser->getRootPath($cwd) ??
                $cwd;
        }

        $config["dir"]["path"] = str_replace('\\', '/',
            $config["dir"]["path"]);

        if ($this->system->getOsFamily() == 'Windows') {
            $localAppData = $this->system->getEnvVariable('LOCALAPPDATA') ?:
                $this->system->getEnvVariable('USERPROFILE') . '/AppData/Local';

            $identifier = ucwords($identifier, '/');
            $config["cache"]["path"] = "$localAppData/$identifier/cache";
            $config["config"]["path"] = "$localAppData/$identifier/config";
            $config["state"]["path"] = "$localAppData/$identifier/state";

        } else {
            $home = $this->system->getEnvVariable('HOME');
            $cache = $this->system->getEnvVariable('XDG_CACHE_HOME') ?:
                "$home/.cache";

            $configuration = $this->system->getEnvVariable('XDG_CONFIG_HOME') ?:
                "$home/.config";

            $state = $this->system->getEnvVariable('XDG_STATE_HOME') ?:
                "$home/.local/state";

            $config["cache"]["path"] = "$cache/$identifier";
            $config["config"]["path"] = "$configuration/$identifier";
            $config["state"]["path"] = "$state/$identifier";
        }
    }
}