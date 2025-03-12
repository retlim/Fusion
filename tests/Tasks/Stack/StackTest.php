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

namespace Valvoid\Fusion\Tests\Tasks\Stack;

use Exception;
use Valvoid\Fusion\Tasks\Stack\Stack;
use Valvoid\Fusion\Tests\Tasks\Stack\Mocks\ContainerMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Stack test.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class StackTest extends Test
{
    protected string|array $coverage = Stack::class;

    public ContainerMock $containerMock;

    public function __construct()
    {
        try {

            // set up
            $this->containerMock = new ContainerMock;
            (new Stack([]))->execute();

            // test
            $this->testStateStructure();
            $this->testLifecycle();

            // clear
            $this->containerMock->destroy();

        } catch (Exception $exception) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ .
                "\n " . $exception->getMessage();

            if (isset($this->containerMock))
                $this->containerMock->destroy();

            $this->result = false;
        }
    }

    public function testStateStructure(): void
    {
        if ($this->containerMock->dir->structure === [

            // internal root
            "state" => [
                "from"=> "packages/metadata1",
                "to" => "state"
            ],

            // moveable
            "state/deps/external/metadata3" => [
                "from" => "packages/metadata3",
                "to" => "state/deps/external/metadata3"
            ],

            // recycle
            "state/whatever/metadata4" => [
                "from" => "packages/metadata4",
                "to" => "state/whatever/metadata4"
            ],
            "state/whatever/metadata5" => [
                "from" => "packages/metadata5",
                "to" => "state/whatever/metadata5"
            ],

            // download
            "state/deps/metadata6"=> [
                "from" => "packages/metadata6",
                "to" => "state/deps/metadata6"
            ]
        ])
            return;

        echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

        $this->result = false;
    }

    public function testLifecycle(): void
    {
        // internal root copy inside state
        foreach ($this->containerMock->group->internalMetas as $id => $internalMeta)
            if (($id == "metadata1" && $internalMeta->onCopy === false) ||
                ($id != "metadata1" && $internalMeta->onCopy === true)) {
                echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " . __LINE__;

                $this->result = false;
                break;
            }

        // external deps
        foreach ($this->containerMock->group->externalMetas as $id => $externalMeta)
            if (($id == "metadata6" && $externalMeta->onDownload === false) ||
                ($id != "metadata6" && $externalMeta->onCopy === false)) {
                echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__ . " | " . __LINE__;

                $this->result = false;
                break;
            }
    }
}