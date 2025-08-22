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

namespace Valvoid\Fusion\Tests\Tasks\Image;

use Exception;
use Valvoid\Fusion\Tasks\Image\Image;
use Valvoid\Fusion\Tests\Tasks\Image\Mocks\BoxMock;
use Valvoid\Fusion\Tests\Tasks\Image\Mocks\BusMock;
use Valvoid\Fusion\Tests\Tasks\Image\Mocks\GroupMock;
use Valvoid\Fusion\Tests\Tasks\Image\Mocks\LogMock;
use Valvoid\Fusion\Tests\Test;

/**
 * Integration test case for the image task.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class ImageTest extends Test
{
    protected string|array $coverage = Image::class;
    protected GroupMock $group;
    public function __construct()
    {
        $box = new BoxMock;
        $this->group = new GroupMock;
        $box->group = $this->group;
        $box->bus = new BusMock;
        $box->log = new LogMock;

        try {
            $task = new Image(["group" => true]);

            $task->execute();
            $this->testMetas();
            $this->testRootMetadata();
            $box::unsetInstance();

        } catch (Exception $exception) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;
            echo "\n " . $exception->getMessage();

            $box::unsetInstance();

            $this->result = false;
        }
    }

    public function testMetas(): void
    {
        $metas = $this->group->getInternalMetas();

        if (sizeof($metas) != 3 || !isset($metas["metadata1"]) ||
            !isset($metas["metadata2"]) || !isset($metas["metadata3"]))
            $this->handleFailedTest();
    }

    public function testRootMetadata(): void
    {
        $metadata = $this->group->getInternalRootMetadata();

        // bot and env files
        if ($metadata->getId() != "metadata1" || $metadata->getVersion() != "2.0.0" ||
            $metadata->getContent()["name"] != "metadata1-dev" ||
            $metadata->getContent()["description"] != "metadata1-local")
            $this->handleFailedTest();
    }
}