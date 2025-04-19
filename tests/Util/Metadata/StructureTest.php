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

namespace Valvoid\Fusion\Tests\Util\Metadata;

use Valvoid\Fusion\Tests\Test;
use Valvoid\Fusion\Util\Metadata\Structure;

/**
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class StructureTest extends Test
{
    protected string|array $coverage = Structure::class;

    /** @var array|array[] Inflated (normalized)  */
    private array $structure = [
        "/dir1" => [
            "adapter1" => [
                "/dir2" => [],
                "path1" => [
                    "path2" => [
                        "ref1"
                    ]
                ]
            ],
        ]
    ];

    public function __construct()
    {
        $this->testFullSource();
        $this->testSuffixSource();
    }

    public function testSuffixSource(): void
    {
        $breadcrumb = Structure::getBreadcrumb(
            $this->structure,
            "path2/ref1"
        );

        if ($breadcrumb !== [
                "/dir1",
                "adapter1" ,
                "path1" ,
                "path2" ,
                "ref1"
            ]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testFullSource(): void
    {
        $breadcrumb = Structure::getBreadcrumb(
            $this->structure,
            "adapter1/path1/path2/ref1"
        );

        if ($breadcrumb !== [
                "/dir1",
                "adapter1" ,
                "path1" ,
                "path2" ,
                "ref1"
            ]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}