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

namespace Valvoid\Fusion\Tests\Tasks\Download\Config;

use Valvoid\Fusion\Tasks\Download\Config\Normalizer;
use Valvoid\Fusion\Tests\Test;

class NormalizerTest extends Test
{
    protected string|array $coverage = Normalizer::class;

    public function __construct()
    {
        $this->testGroup();
        $this->testId();
    }

    public function testGroup(): void
    {
        $config = [];

        Normalizer::normalize(["t", "g", "i"], $config);

        if ($config !== ["group" => "g", "id" => "i"])
            $this->handleFailedTest();
    }

    public function testId(): void
    {
        $config = [];

        Normalizer::normalize(["t", "i"], $config);

        if ($config !== ["id" => "i"])
            $this->handleFailedTest();
    }
}