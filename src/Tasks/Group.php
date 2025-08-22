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

namespace Valvoid\Fusion\Tasks;

use Valvoid\Fusion\Box\Box;
use Valvoid\Fusion\Group\Proxy\Proxy;
use Valvoid\Fusion\Log\Events\Errors\Error;
use Valvoid\Fusion\Metadata\External\External as ExternalMeta;
use Valvoid\Fusion\Metadata\Internal\Internal as InternalMeta;

/**
 * Static task group proxy.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Group
{
    /**
     * Sets internal metas.
     *
     * @param array<string, InternalMeta> $metas Metas.
     * @throws Error Internal error.
     */
    public static function setInternalMetas(array $metas): void
    {
        Box::getInstance()->get(Proxy::class)
            ->setInternalMetas($metas);
    }

    /**
     * Sets implication.
     *
     * @param array $implication Implication.
     * @throws Error Internal error.
     */
    public static function setImplication(array $implication): void
    {
        Box::getInstance()->get(Proxy::class)
            ->setImplication($implication);
    }

    /**
     * Sets external metas.
     *
     * @param array<string, ExternalMeta> $metas Metas.
     * @throws Error Internal error.
     */
    public static function setExternalMetas(array $metas): void
    {
        Box::getInstance()->get(Proxy::class)
            ->setExternalMetas($metas);
    }

    /**
     * Returns optional external root meta.
     *
     * @return ExternalMeta|null Meta.
     * @throws Error Internal error.
     */
    public static function getExternalRootMetadata(): ?ExternalMeta
    {
        return Box::getInstance()->get(Proxy::class)
            ->getExternalRootMetadata();
    }

    /**
     * Returns internal root meta.
     *
     * @return InternalMeta Meta.
     * @throws Error Internal error.
     */
    public static function getInternalRootMetadata(): InternalMeta
    {
        return Box::getInstance()->get(Proxy::class)
            ->getInternalRootMetadata();
    }

    /**
     * Returns root metadata.
     *
     * @return ExternalMeta|InternalMeta Meta.
     * @throws Error Internal error.
     */
    public static function getRootMetadata(): ExternalMeta|InternalMeta
    {
        return Box::getInstance()->get(Proxy::class)
            ->getRootMetadata();
    }

    /**
     * Returns indicator for loadable meta.
     *
     * @return bool Indicator.
     * @throws Error Internal error.
     */
    public static function hasDownloadable(): bool
    {
        return Box::getInstance()->get(Proxy::class)
            ->hasDownloadable();
    }

    /**
     * Returns external metas.
     *
     * @return array<string, ExternalMeta> Metas.
     * @throws Error Internal error.
     */
    public static function getExternalMetas(): array
    {
        return Box::getInstance()->get(Proxy::class)
            ->getExternalMetas();
    }

    /**
     * Returns internal metas.
     *
     * @return array<string, InternalMeta> Metas.
     * @throws Error Internal error.
     */
    public static function getInternalMetas(): array
    {
        return Box::getInstance()->get(Proxy::class)
            ->getInternalMetas();
    }

    /**
     * Sets implication breadcrumb. If set implication starts
     * at runtime layer passed to the Fusion object.
     *
     * @param string[] $breadcrumb Breadcrumb.
     * @throws Error Internal error.
     */
    public static function setImplicationBreadcrumb(array $breadcrumb): void
    {
        Box::getInstance()->get(Proxy::class)
            ->setImplicationBreadcrumb($breadcrumb);
    }

    /**
     * Returns implication.
     *
     * @return array Implication.
     * @throws Error Internal error.
     */
    public static function getImplication(): array
    {
        return Box::getInstance()->get(Proxy::class)
            ->getImplication();
    }

    /**
     * Returns event path.
     *
     * @param string $source Source.
     * @return array Path.
     * @throws Error Internal error.
     */
    public static function getPath(string $source): array
    {
        return Box::getInstance()->get(Proxy::class)
            ->getPath($source);
    }

    /**
     * Returns first match path to a source.
     *
     * @param array $implication Implication.
     * @param string $source Source.
     * @return array Path.
     * @throws Error Internal error.
     */
    public static function getSourcePath(array $implication, string $source): array
    {
        return Box::getInstance()->get(Proxy::class)
            ->getSourcePath($implication, $source);
    }
}