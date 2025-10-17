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

/**
 * Autoloader for prefix-based lazy code and ASAP script files.
 */
class PrefixAutoloader
{
    /** @var array<string, string> Namespace prefix to path map. */
    private array $prefixes = [];

    /** @var string[] ASAP code. */
    private array $asap = [];

    /** @var string Project dir. */
    private string $root;

    /** Constructs the autoloader. */
    public function __construct()
    {
        $this->root = dirname(__DIR__, 2);

        // load as soon as possible code
        foreach ($this->asap as $file)
            require $this->root . $file;
    }

    /** Destroys the autoloader. */
    public function __destruct()
    {
        $this->unregister();
    }

    /**
     * Registers the lazy code autoloader with SPL.
     *
     * @param bool $prepend Whether to prepend the autoloader to the SPL stack.
     * @return bool True on success, false on failure.
     */
    public function register(bool $prepend = false): bool
    {
        return spl_autoload_register($this->loadLazyCode(...),
            prepend: $prepend);
    }

    /**
     * Unregisters the lazy code autoloader from SPL.
     *
     * @return bool True on success, false on failure.
     */
    public function unregister(): bool
    {
        return spl_autoload_unregister($this->loadLazyCode(...));
    }

    /**
     * Loads lazy code on demand.
     *
     * @param string $loadable Identifier.
     */
    private function loadLazyCode(string $loadable): bool
    {
        foreach ($this->prefixes as $prefix => $path)
            if (str_starts_with($loadable, $prefix)) {
                $suffix = substr($loadable, strlen($prefix));
                $suffix = str_replace('\\', '/', $suffix);
                $file = $this->root . "$path$suffix.php";

                if (is_file($file)) {
                    require $file;

                    return true;
                }
            }

        return false;
    }

    /**
     * Adds a namespace prefix and its corresponding path.
     *
     * @param string $prefix Namespace prefix.
     * @param string $path Path corresponding to the prefix.
     */
    public function addPrefix(string $prefix, string $path): void
    {
        $this->prefixes[$prefix] = $path;

        // longer prefix first
        krsort($this->prefixes, SORT_STRING);
    }

    /**
     * Returns the project root directory.
     *
     * @return string Project root path.
     */
    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * Returns ASAP scripts.
     *
     * @return string[] Array of ASAP file paths.
     */
    public function getAsap(): array
    {
        return $this->asap;
    }

    /**
     * Returns namespace prefixes for lazy-loadable code.
     *
     * @return array<string, string> Prefix namespace to path map.
     */
    public function getPrefixes(): array
    {
        return $this->prefixes;
    }
}