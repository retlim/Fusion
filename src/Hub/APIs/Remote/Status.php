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

namespace Valvoid\Fusion\Hub\APIs\Remote;

/**
 * Common remote API status codes.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
enum Status
{
    // 200 OK
    case OK;

    // 401 Unauthorized
    // invalid token like expired
    case UNAUTHORIZED;

    // 403 Forbidden
    // token is ok but scope or whatever
    case FORBIDDEN;

    // 404 Not Found
    // token is ok but other resource scope
    // resource not exist
    case NOT_FOUND;

    // 429 Too Many Requests
    case TO_MANY_REQUESTS;

    // all other codes
    case ERROR;
}