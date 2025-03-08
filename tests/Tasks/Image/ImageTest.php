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
use Valvoid\Fusion\Container\Proxy\Logic;
use Valvoid\Fusion\Tasks\Group;
use Valvoid\Fusion\Tasks\Image\Image;
use Valvoid\Fusion\Tests\Tasks\Image\Mocks\BusMock;
use Valvoid\Fusion\Tests\Tasks\Image\Mocks\ConfigMock;
use Valvoid\Fusion\Tests\Tasks\Image\Mocks\ContainerMock;
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

    public function __construct()
    {
        try {
            $containerMock = new ContainerMock;
            $bus = new BusMock;
            $config = new ConfigMock;
            (new Logic)->get(Group::class);

            $task = new Image(["group" => true]);

            $task->execute();
            $this->testMetas();
            $this->testRootMetadata();
            $bus->destroy();
            (new Logic)->unset(Group::class);
            $config->destroy();
            $containerMock->destroy();

        } catch (Exception $exception) {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;
            echo "\n " . $exception->getMessage();

            $config->destroy();
            $bus->destroy();
            $containerMock->destroy();

            $this->result = false;
        }
    }

    public function testMetas(): void
    {
        $metas = Group::getInternalMetas();

        if (sizeof($metas) != 3 || !isset($metas["metadata1"]) ||
            !isset($metas["metadata2"]) || !isset($metas["metadata3"])) {

            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }

    public function testRootMetadata(): void
    {
        $metadata = Group::getInternalRootMetadata();

        // bot and env files
        if ($metadata->getId() != "metadata1" || $metadata->getVersion() != "2.0.0" ||
            $metadata->getContent()["name"] != "metadata1-dev" ||
            $metadata->getContent()["description"] != "metadata1-local") {
            echo "\n[x] " . __CLASS__ . " | " . __FUNCTION__;

            $this->result = false;
        }
    }
}