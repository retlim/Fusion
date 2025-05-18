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

namespace Valvoid\Fusion\Tests\Hub\APIs\Local\Git\Mocks;

use Valvoid\Fusion\Wrappers\Program;

/**
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class GitMock extends Program
{
    public function execute(string $command, array &$output, int &$result_code): string|false
    {
        if (str_contains($command, " tag ")) {
            $result_code = 0;
            $output = [
                "1.2.3",
                "2.3.4",
                "3.4.5",
            ];
        }

        if (str_contains($command, " rev-parse ")) {
            $result_code = 0;
            $output = ["7fe3f596be4e7a"]; // commit
        }

        if (str_contains($command, " show ")) {
            $result_code = 0;
            $output = [
                '{"version":"_"}' // metadata
            ];
        }

        // archive
        // ------------

        if (str_contains($command, " branch --show-current ")) {
            $result_code = 0;
            $output = [
                "main"
            ];
        }

        if (str_contains($command, " archive ")) {
            $result_code = 0;
            $output = []; // nothing
        }

        return "";
    }
}