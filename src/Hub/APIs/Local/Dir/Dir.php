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
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Valvoid\Fusion\Hub\APIs\Local\Dir;

use Exception;
use PharData;
use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Hub\APIs\Local\Local as LocalApi;
use Valvoid\Fusion\Hub\Responses\Local\Archive;
use Valvoid\Fusion\Hub\Responses\Local\File;
use Valvoid\Fusion\Hub\Responses\Local\References;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Wrappers\File as FileWrapper;

/**
 * Directory hub to get local OS packages.
 */
class Dir extends LocalApi
{
    /**
     * Returns versions.
     *
     * @param string $path Path relative to config root.
     * @return References|string Response or error message.
     * @throws Error Internal error.
     * @throws Exception
     */
    public function getReferences(string $path): References|string
    {
        // directory has only one pointer
        // version inside metadata
        $file = "$this->root$path/fusion.json";
        $wrapper = Box::getInstance()->get(FileWrapper::class);

        if (!$wrapper->exists($file))
            return "Invalid directory (package) content. The required " .
                "metadata file \"$file\" does not exist.";

        $content = $wrapper->get($file);

        if ($content === false)
            return "Can't read the file \"$file\".";

        $metadata = json_decode($content, true);

        if (!isset($metadata["version"]))
            return "Can't extract version from the file \"$file\".";

        // real version
        return new References([$metadata["version"]]);
    }

    /**
     * Returns indicator for existing reference.
     *
     * @param string $path Path.
     * @param string $reference Reference.
     * @return bool Indicator.
     * @throws Error Internal error.
     */
    private function hasReference(string $path, string $reference): bool
    {
        $response = $this->getReferences($path);

        return $response instanceof References &&
            in_array($reference, $response->getEntries());
    }

    /**
     * Returns file content.
     *
     * @param string $path Path.
     * @param string $reference Reference.
     * @param string $filename Filename.
     * @return File|string Response or error message.
     * @throws Error Internal error.
     * @throws Exception
     */
    public function getFileContent(string $path, string $reference, string $filename): File|string
    {
        $file = "$this->root$path$filename";

        if (!$this->hasReference($path, $reference))
            return "Can't get content from the file \"$file\"" .
                " at reference \"$reference\". Reference does not exist.";

        $wrapper = Box::getInstance()->get(FileWrapper::class);

        if (!$wrapper->exists($file))
            return "The file \"$file\" does not exist.";

        $content = $wrapper->get($file);

        if ($content === false)
            return "Can't read the file \"$file\".";

        return new File($content);
    }

    /**
     * Creates archive file inside directory.
     *
     * @param string $path Path.
     * @param string $reference Reference.
     * @param string $dir Directory.
     * @return Archive|string Response or error message.
     * @throws Error Internal error.
     * @throws Exception
     */
    public function createArchive(string $path, string $reference, string $dir): Archive|string
    {
        if (!$this->hasReference($path, $reference))
            return "Can't create the archive \"$dir/archive.zip\"" .
                " of reference \"$reference\". Reference does not exist.";

        $file = "$dir/archive.zip";
        $archive = Box::getInstance()->get(PharData::class, filename: $file);

        $archive->buildFromDirectory($this->root . $path);

        return new Archive($file);
    }
}