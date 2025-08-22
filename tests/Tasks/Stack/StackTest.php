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
use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Tasks\Stack\Stack;
use Valvoid\Fusion\Tests\Tasks\Stack\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Stack\Mocks\BusMock;
use Valvoid\Fusion\Tests\Tasks\Stack\Mocks\DirMock;
use Valvoid\Fusion\Tests\Tasks\Stack\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Stack\Mocks\LogMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Stack test.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class StackTest extends Test
{
    protected string|array $coverage = [
        Stack::class,

        // ballast
        Box::class
    ];

    public BoxMock $box;

    public function __construct()
    {
        $this->box = new BoxMock;
        $this->box->group = new GroupMock;
        $this->box->bus = new BusMock;
        $this->box->log = new LogMock;
        $this->box->dir = new DirMock;

        try {

            // set up
            (new Stack([]))->execute();

            // test
            $this->testStateStructure();
            $this->testLifecycle();


        } catch (Exception) {
            $this->handleFailedTest();
        }

        $this->box::unsetInstance();
    }

    public function testStateStructure(): void
    {
        if ($this->box->dir->structure === [

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

        $this->handleFailedTest();
    }

    public function testLifecycle(): void
    {
        // internal root copy inside state
        foreach ($this->box->group->internalMetas as $id => $internalMeta)
            if (($id == "metadata1" && $internalMeta->onCopy === false) ||
                ($id != "metadata1" && $internalMeta->onCopy === true)) {
                $this->handleFailedTest();
                break;
            }

        // external deps
        foreach ($this->box->group->externalMetas as $id => $externalMeta)
            if (($id == "metadata6" && $externalMeta->onDownload === false) ||
                ($id != "metadata6" && $externalMeta->onCopy === false)) {
                $this->handleFailedTest();
                break;
            }
    }
}