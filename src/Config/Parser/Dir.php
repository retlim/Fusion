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

namespace Valvoid\Fusion\Config\Parser;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Bus\Events\Config as ConfigEvent;
use Valvoid\Fusion\Log\Events\Level;
use Valvoid\Fusion\Bus\Proxy as BusProxy;
use Valvoid\Fusion\Wrappers\Dir as DirWrapper;
use Valvoid\Fusion\Wrappers\File;

/**
 * Directories config parser.
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class Dir
{
    /**
     * Constructs the parser.
     *
     * @param Box $box Dependency injection container.
     * @param DirWrapper $dir Wrapper for standard directory operations.
     * @param BusProxy $bus Event bus.
     * @param File $file Wrapper for standard file operations.
     */
    public function __construct(
        private readonly Box $box,
        private readonly DirWrapper $dir,
        private readonly BusProxy $bus,
        private readonly File $file) {}

    /**
     * Parses the working directory config.
     *
     * @param array $config Directory config to parse.
     */
    public function parse(array &$config): void
    {
        $this->parsePath($config["dir"]["path"]);
    }

    /**
     * Parses path.
     *
     * @param string $path Path entry.
     */
    private function parsePath(string &$path): void
    {
        $path = str_replace('\\', '/', $path);
        $path = explode('/', $path);
        $filenames = [];

        foreach ($path as $filename)
            if ($filename == "..")
                if (!empty($filenames))
                    array_pop($filenames);

                else $this->bus->broadcast(
                    $this->box->get(ConfigEvent::class,
                        message: "The value of the 'path' key, the " .
                        "current working directory, does not point " .
                        "to anything, as it contains a reference " .
                        "(double dot) to a non-existent parent " .
                        "directory.",
                        level: Level::ERROR,
                        breadcrumb: ["dir", "path"],
                        abstract: []
                    ));

            elseif ($filename != '.')
                $filenames[] = $filename;

        $path = implode('/', $filenames);
    }

    /**
     * Returns non-nested path.
     *
     * @param string $path Directory to start.
     * @return string|null Root.
     */
    public function getRootPath(string $path): ?string
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

        return $match;
    }
}