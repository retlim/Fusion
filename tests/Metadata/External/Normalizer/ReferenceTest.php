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

namespace Valvoid\Fusion\Tests\Metadata\External\Normalizer;

use Valvoid\Fusion\Metadata\External\Normalizer\Reference;
use Valvoid\Fusion\Tests\Test;

class ReferenceTest extends Test
{
    /** @var string|array  */
    protected string|array $coverage = Reference::class;

    public function __construct()
    {
        $this->testNormalizedReference();
        $this->testNormalizedOffsetReference();
    }

    public function testNormalizedReference(): void
    {
        // reference as semantic version
        if (Reference::getNormalizedReference("==1.0.0") !== [
            "reference" => "==1.0.0"
            ])
            $this->handleFailedTest();
    }

    public function testNormalizedOffsetReference(): void
    {
        // offset commit|branch|tag reference
        // handle as semantic version
        if (Reference::getNormalizedReference("1.0.0:ref") !== [
                "version" => "1.0.0",
                "reference" => "ref"
            ])
            $this->handleFailedTest();
    }
}