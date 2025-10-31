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

use Valvoid\Fusion\Tasks\Build\Build;
use Valvoid\Fusion\Tasks\Categorize\Categorize;
use Valvoid\Fusion\Tasks\Copy\Copy;
use Valvoid\Fusion\Tasks\Download\Download;
use Valvoid\Fusion\Tasks\Image\Image;
use Valvoid\Fusion\Tasks\Inflate\Inflate;
use Valvoid\Fusion\Tasks\Extend\Extend;
use Valvoid\Fusion\Tasks\Snap\Snap;
use Valvoid\Fusion\Tasks\Register\Register;
use Valvoid\Fusion\Tasks\Replicate\Replicate;
use Valvoid\Fusion\Tasks\Shift\Shift;
use Valvoid\Fusion\Tasks\Stack\Stack;

return [
    "tasks" => [
        "build" => [
            "image" => Image::class,
            "build" => Build::class,
            "categorize" => Categorize::class,
            "download" => Download::class,
            "copy" => Copy::class,
            "extend" => Extend::class,
            "inflate" => Inflate::class,
            "register" => Register::class,
            "snap" => Snap::class,
            "stack" => Stack::class,
            "shift" => Shift::class,
        ],
        "replicate" => [
            "image" => Image::class,
            "replicate" => Replicate::class,
            "categorize" => Categorize::class,
            "download" => Download::class,
            "copy" => Copy::class,
            "extend" => Extend::class,
            "inflate" => Inflate::class,
            "register" => Register::class,
            "snap" => Snap::class,
            "stack" => Stack::class,
            "shift" => Shift::class,
        ],
        "inflate" => [
            "image" => Image::class,
            "inflate" => Inflate::class
        ],
        "register" => [
            "image" => Image::class,
            "inflate" => Inflate::class,
            "register" => Register::class
        ]
    ]
];