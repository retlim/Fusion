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

namespace Valvoid\Fusion\Tests\Tasks\Build\Mocks;

use Closure;
use Valvoid\Fusion\Metadata\External\Builder;
use Valvoid\Fusion\Metadata\External\External;

/**
 * @copyright Valvoid
 * @license SPDX-License-Identifier: GPL-3.0-or-later
 */
class BuilderMock extends Builder {

    private string $dir;
    private string $source;

    private string $version;
    public Closure $metadata;

    public function __construct(string $dir, string $source)
    {
        $this->dir = $dir;
        $this->source = $source;
    }

    public function getParsedSource(): array
    {
        // versions request
        // pattern reference, OR, AND, sing
        return [$this->source];
    }

    public function getNormalizedSource(): array
    {
        // metadata request
        // version reference
        return [
            "source" => $this->source,
            "version" => $this->version,
        ];
    }

    public function getRawDir(): string
    {
        // empty root or nested
        return $this->dir;
    }

    public function getMetadata(): External
    {
        return call_user_func($this->metadata, $this->source, $this->dir, $this->version);
    }

    public function normalizeReference(string $reference): void
    {
        // extract offset before metadata requests
        $this->version = $reference;
    }

    public function addProductionLayer(string $content, string $file): void
    {
        $content = json_decode($content, true);

        if ($content["source"] != $this->source ||
            $content["version"] != $this->version) {}
    }
}