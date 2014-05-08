<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Factory;

use Closure;
use InvalidArgumentException;
use OutOfBoundsException;
use ReflectionClass;
use ReflectionParameter;
use RuntimeException;

class Container
{
    /**
     * @var (string|Closure)[]
     */
    private $aliases = array();

    /**
     * @var array
     */
    private $constructorArguments = array();

    /**
     * @var array
     */
    private $objects;

    /**
     * @var AbstractLinkResolver
     */
    private $linkResolver;

    /**
     * @var callable[]
     */
    private $callbacks = array();

    /**
     * @param AbstractLinkResolver $resolver
     */
    public function __construct(AbstractLinkResolver $resolver = null)
    {
        $resolver           = $resolver ? : new NullResolver();
        $this->linkResolver = $resolver;

        $this->objects = array(
            __CLASS__            => $this,
            get_class($resolver) => $resolver
        );
    }

    /**
     * @param string         $abstract
     * @param string|Closure $concrete
     */
    public function addAlias($abstract, $concrete)
    {
        $abstract = ltrim($abstract, '\\');
        if (is_string($concrete)) {
            $concrete = ltrim($concrete, '\\');
        }
        $this->aliases[$abstract] = $concrete;
    }

    /**
     * @param string $concrete
     * @param mixed  $argument
     */
    public function addConstructorArguments($concrete, $argument /*, ...$arguments */)
    {
        $concrete = ltrim($concrete, '\\');

        // filter the nulls and set the arguments
        $this->constructorArguments[$concrete] = array_filter(
            array_slice(func_get_args(), 1),
            function ($item) {
                return $item !== null;
            }
        );
    }

    /**
     * @param $concrete
     * @param $position
     * @param $argument
     */
    public function setConstructorArgument($concrete, $position, $argument)
    {
        $concrete = ltrim($concrete, '\\');
        if (!isset($this->constructorArguments[$concrete])) {
            $this->constructorArguments[$concrete] = array($position => $argument);
        } else {
            $this->constructorArguments[$concrete][$position] = $argument;
        }
    }

    public function addCallback($concrete, $callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('$callback is not callable.');
        }
        $concrete = ltrim($concrete, '\\');
        if (!isset($this->callbacks[$concrete])) {
            $this->callbacks[$concrete] = array($callback);
        } else {
            $this->callbacks[$concrete][] = $callback;
        }
    }

    /**
     * @param $abstract
     *
     * @return string|Closure
     * @throws OutOfBoundsException
     */
    public function getAlias($abstract)
    {
        $abstract = ltrim($abstract, '\\');
        if (!isset($this->aliases[$abstract])) {
            throw new OutOfBoundsException("{$abstract} is not registered.");
        }

        return $this->aliases[$abstract];
    }

    /**
     * @param $abstract
     *
     * @return array
     */
    public function getConstructorArguments($abstract)
    {
        $abstract = ltrim($abstract, '\\');
        $concrete = $this->findMostConcreteDefinition($abstract);
        if (!isset($this->constructorArguments[$concrete])) {
            $this->constructorArguments[$concrete] = array();
        }

        return $this->constructorArguments[$concrete];
    }

    /**
     * @param object $object
     * @param string $abstract
     *
     * @return object|false
     * @throws InvalidArgumentException
     */
    public function setInstance($object, $abstract = null)
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException('$object must be an object');
        }
        if ($abstract === null) {
            $abstract = get_class($object);
        } else {
            $abstract = ltrim($abstract, '\\');
        }
        $concrete = $this->findMostConcreteDefinition($abstract);
        if (isset($this->objects[$concrete])) {
            $old = $this->objects[$concrete];
        } else {
            $old = false;
        }
        $this->objects[$concrete] = $object;

        return $old;
    }

    /**
     * @param string $abstract
     * @param array  $parameters
     * @param bool   $forceNew
     *
     * @return object
     */
    public function get($abstract, array $parameters = array(), $forceNew = false)
    {
        $abstract = ltrim($abstract, '\\');
        // try to find the constructor arguments for the most concrete definition
        $concrete = $this->findMostConcreteDefinition($abstract);

        if (isset($this->constructorArguments[$concrete])) {
            $parameters = $parameters + $this->constructorArguments[$concrete];
        }

        if (isset($this->objects[$concrete]) && !$forceNew) {
            return $this->objects[$concrete];
        }

        $key = $concrete;
        if (isset($this->aliases[$concrete])) {
            // $concrete is not a string here
            $concrete = $this->aliases[$concrete];
        }
        $object = $this->instantiate($concrete, $parameters);
        $this->callCallbacks($key, $object);

        if (!$forceNew) {
            $this->objects[$key] = $object;
        }

        return $object;
    }

    /**
     * @param $concrete
     * @param $object
     */
    private function callCallbacks($concrete, $object)
    {
        if (isset($this->callbacks[$concrete])) {
            foreach ($this->callbacks[$concrete] as $callback) {
                $callback($object, $this);
            }
        }
    }

    /**
     * @param $class
     *
     * @throws RuntimeException
     * @return string
     */
    private function findMostConcreteDefinition($class)
    {
        $visited = array();
        while (isset($this->aliases[$class]) && is_string($this->aliases[$class])) {
            $class = $this->aliases[$class];
            if (isset($visited[$class])) {
                throw new RuntimeException("Circular aliases detected for class {$class}.");
            }
            $visited[$class] = true;
        }

        return $class;
    }

    /**
     * @param string $concrete
     * @param array  $parameters
     *
     * @return object
     * @throws InvalidArgumentException
     */
    private function instantiate($concrete, array $parameters = array())
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }
        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new InvalidArgumentException("Class {$concrete} is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete;
        }

        $constructorArgs = $constructor->getParameters();

        if (empty($constructorArgs)) {
            return new $concrete;
        }

        $arguments = $this->resolveDependencies($constructorArgs, $parameters);
        if (!empty($parameters)) {
            ksort($arguments);
        }

        return $reflector->newInstanceArgs($arguments);
    }

    /**
     * @param ReflectionParameter[] $dependencies
     * @param array                 $resolved
     *
     * @return array
     */
    private function resolveDependencies(array $dependencies, array $resolved)
    {
        foreach ($resolved as $k => $value) {
            unset($dependencies[$k]);
        }
        foreach ($dependencies as $k => $dependency) {
            $class = $dependency->getClass();
            if ($class === null) {
                // primitive type
                $resolved[$k] = $this->resolvePrimitiveParameter($dependency);
            } else {
                $resolved[$k] = $this->resolveClassParameter($class, $dependency);
            }
        }

        return $this->linkResolver->resolveReferences($resolved);
    }

    /**
     * @param ReflectionParameter $dependency
     *
     * @return mixed
     * @throws OutOfBoundsException
     */
    private function resolvePrimitiveParameter(ReflectionParameter $dependency)
    {
        if ($dependency->isDefaultValueAvailable()) {
            return $dependency->getDefaultValue();
        }

        $class         = $dependency->getDeclaringClass()->getName();
        $method        = $dependency->getDeclaringFunction()->getName();
        $parameterName = $dependency->getName();

        throw new OutOfBoundsException("Parameter {$parameterName} is not supplied for {$class}::{$method}.");
    }

    /**
     * @param ReflectionClass     $class
     * @param ReflectionParameter $dependency
     *
     * @throws InvalidArgumentException
     * @return mixed|object
     */
    private function resolveClassParameter(ReflectionClass $class, ReflectionParameter $dependency)
    {
        try {
            return $this->get($class->getName());
        } catch (InvalidArgumentException $e) {
            if (!$dependency->isDefaultValueAvailable()) {
                throw $e;
            }
        }

        return $dependency->getDefaultValue();
    }
}
