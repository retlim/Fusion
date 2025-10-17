<?php
/**
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

namespace Valvoid\Fusion\Box;

use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use Valvoid\Fusion\Log\Events\Errors\Error;

/**
 * Dependency injection container that abstracts project logic
 * relations to make code more flexible, testable, and maintainable.
 */
class Box
{
    private static ?Box $instance = null;

    /** @var array<string, object|null> Shareable dependencies. */
    private array $references = [];

    /** @var array<string, string> Mapped implementations. */
    private array $abstractions = [];

    /** @var array<string, array<string, mixed>> Default values. */
    private array $arguments = [];

    /**
     * Constructs the container.
     *
     * @param bool $shareable Indicator for a shareable instance.
     * If true, the container will provide itself to dependencies
     * instead of creating a new instance.
     */
    public function __construct(bool $shareable = true)
    {
        if ($shareable)
            $this->references[self::class] = $this;

        self::$instance = $this;
        $this->arguments[Error::class] =

            // workaround
            // until box supports defaults
            ["previous" => null];
    }

    /**
     * Sets default constructor arguments for a given class. These
     * arguments will be injected by name whenever the container
     * creates an instance.
     *
     * @param string $class Fully qualified class name.
     * @param mixed ...$arguments Named arguments (parameter => value).
     */
    public function inject(string $class, mixed ...$arguments): void
    {
        $this->arguments[$class] = $arguments;
    }

    /**
     * Marks classes as shareable so the container instantiates
     * them only once and reuses the same instance whenever
     * requested or injected.
     *
     * @param string ...$classes Fully qualified class names.
     */
    public function recycle(string ...$classes): void
    {
        foreach ($classes as $class)
            $this->references[$class] = null;
    }

    /**
     * Maps a concrete implementation to an abstraction. The
     * container will provide the given implementation whenever the
     * abstraction (interface, abstract class, or parent class) is
     * requested or injected as a dependency.
     *
     * @param string $class Fully qualified implementation class.
     * @param string $abstraction Fully qualified interface, abstract
     * class, or parent class.
     */
    public function map(string $class, string $abstraction): void
    {
        $this->abstractions[$abstraction] = $class;
    }

    /**
     * Returns an instance of the given class.
     *
     * @template T
     * @param class-string<T> $class Fully qualified class name.
     * @param mixed ...$arguments Named arguments (parameter => value)
     * applied on-demand to all classes instantiated during the
     * construction chain, overriding default constructor values.
     * @return T Instance of the requested class.
     * @throws Exception If the dependency cannot be resolved or
     * instantiated.
     */
    public function get(string $class, mixed ...$arguments)
    {
        try {
            return $this->getObject($class, ...$arguments);

        // normalize error
        } catch (ReflectionException $exception) {
            $this->throwNormalizedError($exception);
        }
    }

    /**
     * Removes all container settings for the given class,
     * including shareable references, default arguments,
     * and implementation mappings.
     *
     * @param string $class Fully qualified class name.
     */
    public function unset(string $class): void
    {
        unset($this->references[$class]);
        unset($this->abstractions[$class]);
        unset($this->arguments[$class]);
    }

    /**
     * Returns a dependency object.
     *
     * @param string $class Class name.
     * @param mixed $arguments Static arguments.
     * @return object Dependency object.
     * @throws ReflectionException Error.
     * @throws Exception If the dependency cannot be resolved or
     * instantiated.
     */
    private function getObject(string $class, mixed ...$arguments): object
    {
        $recyclable = false;
        $class = $this->abstractions[$class] ??

            // replace with implementation
            $class;

        // recyclable reference
        if (array_key_exists($class, $this->references)) {
            if ($this->references[$class] !== null)
                return $this->references[$class];

            $recyclable = true;
        }

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        $params = [];

        if ($constructor?->isPublic())
            foreach ($constructor->getParameters() as $param) {
                $name = $param->getName();

                // consume on-demand static argument
                if (array_key_exists($name, $arguments)) {
                    $params[$name] = $arguments[$name];
                    unset($arguments[$name]);

                // consume default static
                } elseif (isset($this->arguments[$class]) &&
                    array_key_exists($name, $this->arguments[$class]))
                    $params[$name] = $this->arguments[$class][$name];

                else {
                    $type = $param->getType();

                    if ($type instanceof ReflectionNamedType &&
                        !$type->isBuiltin())
                        $params[$name] = $this->getObject($type->getName(),

                            // pass static
                            ...$arguments);

                    else $params[$name] = null;
                }
            }

        $object = $params ?
            $reflection->newInstance(...$params) :
            $reflection->newInstance();

        // keep reference
        if ($recyclable)
            $this->references[$class] = $object;

        return $object;
    }

    /**
     * Throws loggable error.
     *
     * @param ReflectionException $exception Exception.
     * @throws Exception Internal error.
     */
    private function throwNormalizedError(ReflectionException $exception): void
    {
        $message = $exception->getMessage();

        while (true) {
            $prev = $exception->getPrevious();

            if ($prev === null)
                break;

            $message .= $prev->getMessage();
            $exception = $prev;
        }

        throw new Exception($message);
    }

    /**
     * @return Box|null
     */
    public static function getInstance(): ?Box
    {
        return self::$instance;
    }

    /**
     * @return void
     */
    public static function unsetInstance(): void
    {
        self::$instance = null;
    }
}
