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

namespace Valvoid\Fusion\Tests\Hub\APIs\Remote\GitHub\Config;

use Valvoid\Fusion\Hub\APIs\Remote\GitHub\Config\Normalizer;
use Valvoid\Fusion\Tests\Test;

/**
 * Config normalizer test.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class NormalizerTest extends Test
{
    /** @var string|array Code coverage. */
    protected string|array $coverage = Normalizer::class;

    public function __construct()
    {
        $this->testDefault();
        $this->testCustom();
    }

    public function testDefault(): void
    {
        $config = [];

        Normalizer::normalize(["hub", "apis", "github.com"], $config);

        if ($config !== [
                "tokens" => [],
                "protocol" => "https",
                "domain" => "github.com",
                "url" => "https://api.github.com/repos"
            ]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testCustom(): void
    {
        $config = [
            "tokens" => "c1",
            "protocol" => "c2"
        ];

        Normalizer::normalize(["hub", "apis", "c3"], $config);

        if ($config !== [
                "tokens" => "c1",
                "protocol" => "c2",
                "domain" => "c3",
                "url" => "c2://c3/api/v3/repos"
            ]) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}