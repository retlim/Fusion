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

namespace Valvoid\Fusion\Wrappers;

/**
 * Stream wrapper.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Stream
{
    /** @var false|resource File stream. */
    protected $stream;

    /**
     * Construct the wrapper.
     *
     * @param string $file File.
     * @param string $mode Mode.
     */
    public function __construct(string $file, string $mode)
    {
        $this->stream = fopen($file, $mode);
    }

    /**
     * Returns stream.
     *
     * @return false|resource Stream resource, or false on error.
     */
    public function get(): mixed
    {
        return $this->stream;
    }

    /**
     * Reads remainder of a stream into a string.
     *
     * @return string|false A string or false on failure.
     */
    public function getContents(): string|false
    {
        return stream_get_contents($this->stream);
    }

    /**
     * Rewinds the position of a file pointer.
     *
     * @return bool True on success or false on failure.
     */
    public function rewind(): bool
    {
        return rewind($this->stream);
    }

    /**
     * Closes an open file pointer.
     *
     * @return bool True on success or false on failure.
     */
    public function close(): bool
    {
        return fclose($this->stream);
    }
}