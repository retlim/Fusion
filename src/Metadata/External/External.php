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

namespace Valvoid\Fusion\Metadata\External;

use Valvoid\Fusion\Dir\Dir;
use Valvoid\Fusion\Log\Events\Errors\Lifecycle;
use Valvoid\Fusion\Log\Log;
use Valvoid\Fusion\Metadata\Metadata;
use Valvoid\Fusion\Tasks\Group;

/**
 * External metadata.
 */
class External extends Metadata
{
    /** @var Category Category. */
    private Category $category;

    /** @var array Implication path. */
    private array $path;

    /**
     * Sets category.
     *
     * @param Category $category Category.
     */
    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    /**
     * Returns category.
     *
     * @return Category|null Category.
     */
    public function getCategory(): ?Category
    {
        return $this->category ?? null;
    }

    /**
     * Returns source.
     *
     * @return array{
     *     api: string,
     *     path: string,
     *     reference: string,
     *     prefix: string
     * } Source.
     */
    public function getSource(): array
    {
        return $this->content["source"];
    }

    /**
     * Handles lifecycle error.
     *
     * @param int $errno
     * @param string $message Message.
     * @throws Lifecycle
     */
    protected function errorHandlerCallback(int $errno, string $message): void
    {
        ob_clean();

        if ($errno == E_USER_ERROR)
            $this->throwLifecycleError($message);

        $lifecycle = $this->box->get(Lifecycle::class,
            message: $message,
            layer: array_key_first($this->layers),
            breadcrumb: $this->getLifecycleBreadcrumb(),
            path: $this->getPath()
        );

        match ($errno) {
            E_USER_NOTICE => $this->box->get(Log::class)->notice($lifecycle),
            E_USER_WARNING => $this->box->get(Log::class)->warning($lifecycle),
            default => $this->box->get(Log::class)->info($lifecycle)
        };

        // log
        // print non-error
        ob_flush();
    }

    /**
     * Throws lifecycle error.
     *
     * @param string $message Message.
     * @throws Lifecycle Error.
     */
    protected function throwLifecycleError(string $message): void
    {
        throw $this->box->get(Lifecycle::class,
            message: $message,
            layer: array_key_first($this->layers),
            breadcrumb: $this->getLifecycleBreadcrumb(),
            path:$this->getPath()
        );
    }

    /**
     * Returns source path.
     *
     * @return array Path.
     */
    private function getPath(): array
    {
        return $this->path ??=
            $this->box->get(Group::class)
                ::getPath($this->layers["object"]["source"]);
    }

    /**
     * Triggers optional lifecycle download callback and returns
     * triggered or not indicator.
     *
     * @return bool Indicator.
     */
    public function onDownload(): bool
    {
        if (!isset($this->content["lifecycle"]["download"]))
            return false;

        $this->lifecycle = [
            "state" => "download",
            "root" => $this->box->get(Dir::class)->getStateDir() . $this->getDir(),
            "file" => $this->content["lifecycle"]["download"]
        ];

        // break variable scope
        return $this->requireCallback();
    }

    /**
     * Triggers optional lifecycle install callback and returns
     * triggered or not indicator.
     *
     * @return bool Indicator.
     */
    public function onInstall(): bool
    {
        if (!isset($this->content["lifecycle"]["install"]))
            return false;

        $this->lifecycle = [
            "state" => "install",
            "root" => $this->box->get(Dir::class)->getRootDir() . $this->getDir(),
            "file" => $this->content["lifecycle"]["install"]
        ];

        // break variable scope
        return $this->requireCallback();
    }

    /**
     * Triggers optional lifecycle migrate callback and returns
     * triggered or not indicator.
     *
     * @return bool Indicator.
     */
    public function onMigrate(): bool
    {
        if (!isset($this->content["lifecycle"]["migrate"]))
            return false;

        $id = $this->getId();
        $metadata = $this->box->get(Group::class)::getInternalMetas()[$id];
        $this->lifecycle = [
            "state" => "migrate",
            "root" => $this->box->get(Dir::class)->getPackagesDir() ."/$id",
            "file" => $this->content["lifecycle"]["migrate"]
        ];

        // break variable scope
        return $this->requireCallback([

            // actually source and dir are same
            // but source is absolute and dir is relative to root path
            "dir" => $metadata->getSource(),
            "version" => $metadata->getVersion()
        ]);
    }
}