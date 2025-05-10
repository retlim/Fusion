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

namespace Valvoid\Fusion\Container\Proxy;

use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionUnionType;
use Valvoid\Fusion\Log\Events\Errors\Error;

/**
 * Default dependency container implementation.
 *
 * @copyright Valvoid
 * @license GNU GPLv3
 */
class Logic implements Proxy
{
    /** @var array<string, string|object>  */
    protected array $references = [];

    /**
     * Returns an instantiated dependency.
     *
     * @template T
     * @param class-string<T> $class Class name.
     * @param mixed $args Static arguments.
     * @return T Instantiated dependency.
     * @throws Error Internal error.
     */
    public function get(string $class, mixed ...$args): object
    {
        try {
            if (isset($this->references[$class])) {
                $object = $this->references[$class];

                if (is_string($object)) {
                    $object = $this->getObject($object, ...$args);
                    $this->references[$class] = $object;
                }

            } else
                $object = $this->getObject($class, ...$args);

            return $object ??
                throw new Error(
                    "Can't instantiate dependency class " .
                    "\"$class\". No constructor found."
                );

        // normalize error
        } catch (ReflectionException $exception) {
            $this->throwNormalizedError($exception);
        }
    }

    /**
     * Creates a sharable instance reference.
     *
     * @param string $id Identifier.
     * @param string $class Implementation.
     */
    public function refer(string $id, string $class): void
    {
        $this->references[$id] = $class;
    }

    /**
     * Returns an optional dependency object.
     *
     * @param string $class Class name.
     * @param mixed $args Static arguments.
     * @return object|null Optional dependency object.
     * @throws ReflectionException Error.
     */
    protected function getObject(string $class, mixed ...$args): ?object
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        // top object is required one
        // nested parameter can be:
        // nullable, interface with default value, ...
        if ($constructor === null)

            // has built-in constructor
            return $reflection->isInstantiable() ?
                $reflection->newInstance() :
                null;

        $params = [];

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();

            // consume static argument
            if (isset($args[$name])) {
                $params[$name] = $args[$name];
                unset($args[$name]);

            } else {
                $type = $param->getType();

                if ($type instanceof ReflectionNamedType) {
                    if (!$type->isBuiltin())
                        $params[$name] = $this->getObject($type->getName(),

                            // pass static
                            ...$args);

                // fall back to default
                // loop all until not null
                } elseif ($type instanceof ReflectionUnionType ||
                    $type instanceof ReflectionIntersectionType) {
                    foreach ($type->getTypes() as $subType)
                        if (!$subType->isBuiltin()) {
                            $parameter = $this->getObject($subType->getName(),

                                // pass static
                                ...$args);

                            // first match from left
                            if ($parameter !== null) {
                                $params[$name] = $parameter;

                                break;
                            }
                        }

                } else
                    $params[$name] = null;
            }
        }

        // ordinary
        if ($constructor->isPublic())
            return $params ?
                $reflection->newInstance(...$params) :
                $reflection->newInstance();

        // proxy, singleton, ...
        // clean public interface with hidden constructor
        // fall back to magic function
        $object = $reflection->newInstanceWithoutConstructor();
        $params ?
            $constructor->invoke($object, ...$params) :
            $constructor->invoke($object);

        return $object;
    }

    /**
     * Unsets static properties by setting default values.
     *
     * @param string $class Class name.
     * @throws Error Internal error.
     */
    public function unset(string $class): void
    {
        try {
            $reflection = new ReflectionClass($class);

            foreach ($reflection->getProperties() as $property)
                if ($property->isStatic())
                    $property->setValue(null, $property->getDefaultValue());

        // normalize error
        } catch (ReflectionException $exception) {
            $this->throwNormalizedError($exception);
        }
    }

    /**
     * Throws loggable error.
     *
     * @param ReflectionException $exception Exception.
     * @throws Error Internal error.
     */
    protected function throwNormalizedError(ReflectionException $exception): void
    {
        $message = $exception->getMessage();

        while (true) {
            $prev = $exception->getPrevious();

            if ($prev === null)
                break;

            $message .= $prev->getMessage();
            $exception = $prev;
        }

        throw new Error($message);
    }
}