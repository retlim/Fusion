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

namespace Valvoid\Fusion\Config\Normalizer;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Bus;
use Valvoid\Fusion\Bus\Events\Config as ConfigEvent;
use Valvoid\Fusion\Config\Parser\Dir as DirectoryParser;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Wrappers\System;
use Valvoid\Fusion\Wrappers\Dir as DirWrapper;

/**
 * Working directory config normalizer.
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class Dir
{
    /**
     * Constructs the normalizer.
     *
     * @param Box $box Dependency injection container.
     * @param DirWrapper $wrapper Wrapper for standard directory operations.
     * @param System $system Wrapper for standard system operations.
     */
    public function __construct(
        private readonly Box $box,
        private readonly DirWrapper $wrapper,
        private readonly System $system) {}

    /**
     * Normalizes the working directory config.
     *
     * @param array $config Config to normalize.
     */
    public function normalize(array &$config): void
    {
        $config["dir"]["creatable"] ??= true;
        $config["dir"]["clearable"] ??= false;

        // automatic detection
        if(!isset($config["dir"]["path"])) {
            $cwd = getcwd();

            // some unix variants
            if (!$cwd)
                Bus::broadcast(new ConfigEvent(
                    "Can't set the default value for the \"path\" " .
                    "key. Looks like not all parent directories have readable " .
                    "or search mode set.",
                    Level::ERROR,
                    ["dir", "path"]
                ));

            $config["dir"]["path"] = DirectoryParser::getNonNestedPath($cwd) ??

                // no parent
                // take as it is
                $cwd;
        }

        $config["dir"]["path"] = str_replace('\\', '/',
            $config["dir"]["path"]);

        if (PHP_OS_FAMILY == 'Windows') {
            $localAppData = $this->system->getEnvVariable('LOCALAPPDATA')
                ?: ($this->system->getEnvVariable('USERPROFILE') . '/AppData/Local');

            $config["cache"]["path"] = "$localAppData/Valvoid/Fusion/cache";
            $config["config"]["path"] = "$localAppData/Valvoid/Fusion/config";
            $config["state"]["path"] = "$localAppData/Valvoid/Fusion/state";

        } else {
            $home = $this->system->getEnvVariable('HOME');
            $cache = $this->system->getEnvVariable('XDG_CACHE_HOME') ?: "$home/.cache";
            $conf  = $this->system->getEnvVariable('XDG_CONFIG_HOME') ?: "$home/.config";
            $state = $this->system->getEnvVariable('XDG_STATE_HOME') ?: "$home/.local/state";

            $config["cache"]["path"] = "$cache/valvoid/fusion";
            $config["config"]["path"] = "$conf/valvoid/fusion";
            $config["state"]["path"] = "$state/valvoid/fusion";
        }
    }
}