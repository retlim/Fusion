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

namespace Valvoid\Fusion\Wrappers;

use CurlHandle;

/**
 * Curl wrapper.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Curl
{
    /** @var CurlHandle Curl handle. */
    protected CurlHandle $handle;

    /** Constructs the wrapper. */
    public function __construct()
    {
        $this->handle = curl_init();
    }

    /** Destructs the wrapper. */
    public function __destruct()
    {
        curl_close($this->handle);
    }

    /**
     * Sets an option for a cURL transfer.
     *
     * @param int $option Option ID.
     * @param mixed $value Value for the option.
     * @return bool True on success or false on failure.
     */
    public function setOption(int $option, mixed $value): bool
    {
        return curl_setopt($this->handle, $option, $value);
    }

    /**
     * Sets an option for a cURL transfer.
     *
     * @return bool True on success or false on failure.
     */
    public function setShareOption(CurlShare $curlShare): bool
    {
        return curl_setopt($this->handle, CURLOPT_SHARE,

            // wrapper abstraction
            $curlShare->getHandle());
    }

    /**
     * Sets multiple options for a cURL transfer.
     *
     * @param array $options Option ID.
     * @return bool True on success or false on failure.
     */
    public function setOptions(array $options): bool
    {
        return curl_setopt_array($this->handle, $options);
    }

    /**
     * Returns info regarding a specific transfer.
     *
     * @param int|null $option
     * @return mixed
     */
    public function getInfo(?int $option): mixed
    {
        return curl_getinfo($this->handle, $option);
    }

    /**
     * Resets options.
     */
    public function reset(): void
    {
        curl_reset($this->handle);
    }

    /**
     * Returns curl handle.
     *
     * @return CurlHandle Handle.
     */
    public function getHandle(): CurlHandle
    {
        return $this->handle;
    }

    /**
     * Returns last error number.
     *
     * @return int Error number or 0 (zero) if no error occurred.
     */
    public function getErrorCode(): int
    {
        return curl_errno($this->handle);
    }

    /**
     * Returns string describing the given error code.
     *
     * @param int $code Error ID.
     * @return string Error message.
     */
    public function getErrorMessage(int $code): string
    {
        return curl_strerror($code) ??
            "Unknown error";
    }
}