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

namespace Valvoid\Fusion\Wrappers;

/**
 * External program wrapper.
 */
class Program
{
    /**
     * Execute an external program.
     *
     * @param string $command The command that will be executed.
     * @param array $output If the output argument is present, then the
     * specified array will be filled with every line of output from the command.
     * Trailing whitespace, such as \n, is not included in this array. Note that
     * if the array already contains some elements, exec will append to the end
     * of the array. If you do not want the function to append elements, call
     * unset on the array before passing it to exec.
     * @param int $result_code If the result_code argument is present along with
     * the output argument, then the return status of the executed command will
     * be written to this variable.
     * @return string|false The last line from the result of the command.
     * If you need to execute a command and have all the data from the command
     * passed directly back without any interference, use the passthru function.
     * To get the output of the executed command, be sure to set and use the output parameter.
     */
    public function execute(string $command, array &$output, int &$result_code): string|false
    {
        return exec($command, $output, $result_code);
    }
}