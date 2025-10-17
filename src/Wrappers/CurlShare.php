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

namespace Valvoid\Fusion\Wrappers;

use CurlShareHandle;

/**
 * Curl share wrapper.
 */
class CurlShare
{
    /** @var CurlShareHandle Curl share handle. */
    protected CurlShareHandle $handle;

    /** Constructs the wrapper. */
    public function __construct()
    {
        $this->handle = curl_share_init();
    }

    /** Destructs the wrapper. */
    public function __destruct()
    {
        curl_share_close($this->handle);
    }

    /**
     * Sets an option for a cURL share handle.
     *
     * @param int $option Option ID.
     * @param mixed $value Value for the option.
     * @return bool True on success or false on failure.
     */
    public function setOption(int $option, mixed $value): bool
    {
        return curl_share_setopt($this->handle, $option, $value);
    }

    /**
     * Returns curl share handle.
     *
     * @return CurlShareHandle Handle.
     */
    public function getHandle(): CurlShareHandle
    {
        return $this->handle;
    }

    /**
     * Returns last share curl error number.
     *
     * @return int Error number or 0 (zero) if no error occurred.
     */
    public function getErrorCode(): int
    {
        return curl_share_errno($this->handle);
    }

    /**
     * Returns string describing the given error code.
     *
     * @param int $code Error ID.
     * @return string Error message.
     */
    public function getErrorMessage(int $code): string
    {
        return curl_share_strerror($code) ??
            "Unknown error";
    }
}