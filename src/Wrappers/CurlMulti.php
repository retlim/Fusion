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

use CurlMultiHandle;

/**
 * Curl multi wrapper.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class CurlMulti
{
    /** @var CurlMultiHandle Curl multi handle. */
    protected CurlMultiHandle $handle;

    /** Constructs the wrapper. */
    public function __construct()
    {
        $this->handle = curl_multi_init();
    }

    /** Destructs the wrapper. */
    public function __destruct()
    {
        curl_multi_close($this->handle);
    }

    /**
     * Sets an option for a cURL muti handle.
     *
     * @param int $option Option ID.
     * @param mixed $value Value for the option.
     * @return bool True on success or false on failure.
     */
    public function setOption(int $option, mixed $value): bool
    {
        return curl_multi_setopt($this->handle, $option, $value);
    }

    /**
     * Adds a normal cURL handle to a cURL multi handle.
     *
     * @param Curl $curl Normal cURL handle wrapper.
     * @return int 0 on success, or one of the CURLM_XXX errors code.
     */
    public function addCurl(Curl $curl): int
    {
        return curl_multi_add_handle($this->handle, $curl->getHandle());
    }

    /**
     * Removes normal cURL handle from cURL multi handle.
     *
     * @param Curl $curl Normal cURL handle wrapper.
     * @return int 0 on success, or one of the CURLM_XXX errors code.
     */
    public function removeCurl(Curl $curl): int
    {
        return curl_multi_remove_handle($this->handle, $curl->getHandle());
    }

    /**
     * Returns information about the current transfers.
     *
     * @return array|false On success, returns an associative array for the message, false on failure.
     */
    public function getAllInfo(): array|false
    {
        return curl_multi_info_read($this->handle);
    }

    /**
     * Returns ID.
     *
     * @param mixed $handle Handle.
     * @return int ID.
     */
    public function getId(mixed $handle): int
    {
        return curl_getinfo($handle, CURLINFO_PRIVATE);
    }

    /**
     * Runs the sub-connections of the current cURL handle.
     *
     * @param int $operations A reference to a flag to tell whether the
     * operations are still running.
     * @return int A cURL code defined in the cURL Predefined Constants.
     * This only returns errors regarding the whole multi stack. There
     * might still have occurred problems on individual transfers even
     * when this function returns CURLM_OK.
     */
    public function exec(int &$operations): int
    {
        return curl_multi_exec($this->handle,  $operations);
    }

    /**
     * Waits for activity on any curl_multi connection.
     *
     * @param float $timeout Time, in seconds, to wait for a response.
     * @return int On success, returns the number of descriptors contained in,
     * the descriptor sets. On failure, this function will return -1 on a
     * select failure or timeout (from the underlying select system call).
     */
    public function select(float $timeout = 1.0): int
    {
        return curl_multi_select($this->handle, $timeout);
    }

    /**
     * Returns the content of a cURL handle
     *
     * @param Curl $curl Normal cURL handle wrapper.
     * @return ?string Content of a cURL handle if CURLOPT_RETURNTRANSFER is set.
     */
    public function getContent(Curl $curl): ?string
    {
        return curl_multi_getcontent($curl->getHandle());
    }

    /**
     * Returns last multi curl error number.
     *
     * @return int Error number or 0 (zero) if no error occurred.
     */
    public function getErrorCode(): int
    {
        return curl_multi_errno($this->handle);
    }

    /**
     * Returns string describing the given error code.
     *
     * @param int $code Error ID.
     * @return string Error message.
     */
    public function getErrorMessage(int $code): string
    {
        return curl_multi_strerror($code) ??
            "Unknown error";
    }
}