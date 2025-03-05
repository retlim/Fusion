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

namespace Valvoid\Fusion\Tests\Config\Parser\Hub\Mocks;

use Valvoid\Fusion\Hub\APIs\Local\Local;
use Valvoid\Fusion\Hub\Responses\Local\Archive;
use Valvoid\Fusion\Hub\Responses\Local\File;
use Valvoid\Fusion\Hub\Responses\Local\References;

/**
 * Mocked APi.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ApiMock extends Local
{
    public function getReferences(string $path): References|string
    {
        return "";
    }

    public function getFileContent(string $path, string $reference, string $filename): File|string
    {
        return "";
    }

    public function createArchive(string $path, string $reference, string $dir): Archive|string
    {
        return "";
    }
}