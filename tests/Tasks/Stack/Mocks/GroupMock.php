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

namespace Valvoid\Fusion\Tests\Tasks\Stack\Mocks;

use Valvoid\Fusion\Group\Proxy\Proxy;
use Valvoid\Fusion\Metadata\External\External as ExternalMeta;
use Valvoid\Fusion\Metadata\External\Category as ExternalCategory;
use Valvoid\Fusion\Metadata\Internal\Category as InternalCategory;
use Valvoid\Fusion\Metadata\Internal\Internal as InternalMeta;

/**
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class GroupMock implements Proxy
{
    public InternalMeta $root;
    /** @var array<InternalMeta>  */
    public array $internalMetas = [];
    /** @var array<ExternalMeta>  */
    public array $externalMetas = [];

    public function __construct()
    {
        $content = [
            "id" => "metadata1",
            "version" => "",
            "name" => "",
            "description" => "",
            "dir" => "", // root
            "source" => "",
        ];

        $this->root = $this->getInternalMeta($content);

        // ----------------------------

        $this->internalMetas["metadata1"] = $this->root;

        // delete
        $content["id"] = "metadata2";
        $content["dir"] = "/deps/metadata2";
        $this->internalMetas["metadata2"] =  $this->getInternalMeta($content);
        $this->internalMetas["metadata2"]->setCategory(InternalCategory::OBSOLETE);

        // merge
        $content["id"] = "metadata3";
        $content["dir"] = "/deps/metadata3";
        $this->internalMetas["metadata3"] =  $this->getInternalMeta($content);
        $this->internalMetas["metadata3"]->setCategory(

            // external to dir
            InternalCategory::MOVABLE);

        // recycle
        $content["id"] = "metadata4";
        $content["dir"] = "/whatever/metadata4";
        $this->internalMetas["metadata4"] =  $this->getInternalMeta($content);

        // recycle
        $content["id"] = "metadata5";
        $content["dir"] = "/whatever/metadata5";
        $this->internalMetas["metadata5"] =  $this->getInternalMeta($content);

        // ----------------------------

        // merge
        $content["id"] = "metadata3";
        $content["dir"] = "/deps/external/metadata3";
        $this->externalMetas["metadata3"] =  $this->getExternalMeta($content);
        $this->externalMetas["metadata3"]->setCategory(

            // override internal to dir
            ExternalCategory::REDUNDANT);

        $content["id"] = "metadata4";
        $content["dir"] = "/whatever/metadata4";
        $this->externalMetas["metadata4"] =  $this->getExternalMeta($content);

        $content["id"] = "metadata5";
        $content["dir"] = "/whatever/metadata5";
        $this->externalMetas["metadata5"] =  $this->getExternalMeta($content);

        // new
        $content["id"] = "metadata6";
        $content["dir"] = "/deps/metadata6";
        $this->externalMetas["metadata6"] =  $this->getExternalMeta($content);
        $this->externalMetas["metadata6"]->setCategory(ExternalCategory::DOWNLOADABLE);
    }

    public function getInternalMeta(array $content): InternalMeta
    {
        return new class($content) extends InternalMeta
        {
            public bool $onCopy = false;

            public function __construct(public array $content) {}

            public function onCopy(): bool
            {
                // 1
                $this->onCopy = true;
                return true;
            }
        };
    }

    public function getExternalMeta(array $content): ExternalMeta
    {
        return new class($content) extends ExternalMeta
        {
            public bool $onCopy = false;
            public bool $onDownload = false;

            public function __construct(public array $content) {}

            public function onDownload(): bool
            {
                // 6
                $this->onDownload = true;
                return true;
            }

            public function onCopy(): bool
            {
                // 4,3,5
                $this->onCopy = true;
                return true;
            }
        };
    }

    public function getExternalRootMetadata(): ?ExternalMeta
    {
        // trigger fallback to internal root
        // use internal since same logic
        return null;
    }

    public function getInternalRootMetadata(): InternalMeta
    {
        return $this->root;
    }

    public function hasDownloadable(): bool
    {
        // has state
        return true;
    }

    public function getExternalMetas(): array
    {
        return $this->externalMetas;
    }

    public function getInternalMetas(): array
    {
        return $this->internalMetas;
    }

    public function getImplication(): array
    {
       return [
           "metadata3" => [
               "source" => "metadata3",

               // actually not nested
               // just test recursive loop
               "implication" => [
                   "metadata4" => [
                       "source" => "metadata4",
                       "implication" => []
                   ],
               ]
           ],
           "metadata5" => [
               "source" => "metadata5",
               "implication" => []
           ],
           "metadata6" => [
               "source" => "metadata6",
               "implication" => []
           ],
       ];
    }

    public function setImplicationBreadcrumb(array $breadcrumb): void {}
    public function getPath(string $source): array { return []; }
    public function getRootMetadata(): ExternalMeta|InternalMeta { return new InternalMeta([], []); }
    public function getSourcePath(array $implication, string $source): array { return []; }
    public function setInternalMetas(array $metas): void {}
    public function setImplication(array $implication): void {}
    public function setExternalMetas(array $metas): void {}
}