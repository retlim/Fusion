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

namespace Valvoid\Fusion\Tests\Tasks\Image\Mocks;

use Valvoid\Fusion\Metadata\Internal\Builder;
use Valvoid\Fusion\Metadata\Internal\Internal;

class BuilderMock extends Builder
{
    public MetadataMock $metadata;
    public array $production = [
        "file" => "",
        "content" => ""
    ];
    public array $local = [
        "file" => "",
        "content" => ""
    ];
    public array $development = [
        "file" => "",
        "content" => ""
    ];
    public array $bot = [
        "file" => "",
        "content" => ""
    ];

    public function __construct(
        public string $dir,
        public string $source) {}

    public function addProductionLayer(string $content, string $file): void
    {
        $this->production = [
            'file' => $file,
            'content' => $content
        ];
    }

    public function addLocalLayer(array $content, string $file): void
    {
        $this->local = [
            'file' => $file,
            'content' => $content
        ];
    }

    public function addDevelopmentLayer(array $content, string $file): void
    {
        $this->development = [
            'file' => $file,
            'content' => $content
        ];
    }

    public function addBotLayer(array $content, string $file): void
    {
        $this->bot = [
            'file' => $file,
            'content' => $content
        ];
    }

    public function getMetadata(): Internal
    {
        return $this->metadata;
    }
}