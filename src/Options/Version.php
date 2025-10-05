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

use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Wrappers\File;

/**
 * Flag option to get current Fusion version.
 *
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class Version
{
    /** @var string Semantic version. */
    public readonly string $semver;

    /**
     * Constructs the option.
     *
     * @param File $file Wrapper for standard file operations.
     * @throws Error Internal error.
     */
    public function __construct(File $file)
    {
        $filename = __DIR__ . "/../../fusion.json";
        $metadata = $file->get($filename);

        if ($metadata === false)
            throw new Error(
                "Cant read metadata file '$filename'."
            );

        $metadata = json_decode($metadata, true);

        if ($metadata === null)
            throw new Error(
                "Cant decode metadata file '$filename'."
            );

        $this->semver = $metadata["version"];
    }
}