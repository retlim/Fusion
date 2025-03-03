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

use Valvoid\Fusion\Group\Proxy\Instance;
use Valvoid\Fusion\Group\Proxy\Proxy;
use Valvoid\Fusion\Metadata\Internal\Internal as InternalMeta;
use Valvoid\Fusion\Metadata\External\External as ExternalMeta;

/**
 * Static task group proxy.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Group
{
    /** @var ?Group Runtime instance. */
    private static ?Group $instance = null;

    /** @var Proxy Decoupled logic. */
    protected Proxy $logic;

    /**
     * Constructs the task group.
     *
     * @param Proxy|Instance $logic Any or default instance logic.
     */
    private function __construct(Proxy|Instance $logic)
    {
        // singleton
        self::$instance ??= $this;
        $this->logic = $logic;
    }

    /**
     * Destroys the instance.
     *
     * @return bool True for success.
     */
    public function destroy(): bool
    {
        self::$instance = null;

        return true;
    }

    /**
     * Sets internal metas.
     *
     * @param array<string, InternalMeta> $metas Metas.
     */
    public static function setInternalMetas(array $metas): void
    {
        self::$instance->logic->setInternalMetas($metas);
    }

    /**
     * Sets implication.
     *
     * @param array $implication Implication.
     */
    public static function setImplication(array $implication): void
    {
        self::$instance->logic->setImplication($implication);
    }

    /**
     * Sets external metas.
     *
     * @param array<string, ExternalMeta> $metas Metas.
     */
    public static function setExternalMetas(array $metas): void
    {
        self::$instance->logic->setExternalMetas($metas);
    }

    /**
     * Returns optional external root meta.
     *
     * @return ExternalMeta|null Meta.
     */
    public static function getExternalRootMetadata(): ?ExternalMeta
    {
        return self::$instance->logic->getExternalRootMetadata();
    }

    /**
     * Returns internal root meta.
     *
     * @return InternalMeta Meta.
     */
    public static function getInternalRootMetadata(): InternalMeta
    {
        return self::$instance->logic->getInternalRootMetadata();
    }

    /**
     * Returns root metadata.
     *
     * @return ExternalMeta|InternalMeta Meta.
     */
    public static function getRootMetadata(): ExternalMeta|InternalMeta
    {
        return self::$instance->logic->getRootMetadata();
    }

    /**
     * Returns indicator for loadable meta.
     *
     * @return bool Indicator.
     */
    public static function hasDownloadable(): bool
    {
        return self::$instance->logic->hasDownloadable();
    }

    /**
     * Returns external metas.
     *
     * @return array<string, ExternalMeta> Metas.
     */
    public static function getExternalMetas(): array
    {
        return self::$instance->logic->getExternalMetas();
    }

    /**
     * Returns internal metas.
     *
     * @return array<string, InternalMeta> Metas.
     */
    public static function getInternalMetas(): array
    {
        return self::$instance->logic->getInternalMetas();
    }

    /**
     * Sets implication breadcrumb. If set implication starts
     * at runtime layer passed to the Fusion object.
     *
     * @param string[] $breadcrumb Breadcrumb.
     */
    public static function setImplicationBreadcrumb(array $breadcrumb): void
    {
        self::$instance->logic->setImplicationBreadcrumb($breadcrumb);
    }

    /**
     * Returns implication.
     *
     * @return array Implication.
     */
    public static function getImplication(): array
    {
        return self::$instance->logic->getImplication();
    }

    /**
     * Returns event path.
     *
     * @param string $source Source.
     * @return array Path.
     */
    public static function getPath(string $source): array
    {
        return self::$instance->logic->getPath($source);
    }

    /**
     * Returns first match path to a source.
     *
     * @param array $implication Implication.
     * @param string $source Source.
     * @return array Path.
     */
    public static function getSourcePath(array $implication, string $source): array
    {
        return self::$instance->logic->getSourcePath($implication, $source);
    }
}