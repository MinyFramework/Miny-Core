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
    private $objects = array();

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
        if (!$resolver) {
            $resolver = new NullResolver();
        }
        $this->linkResolver         = $resolver;

        $this->setInstance($this, __CLASS__);
        $this->setInstance($resolver);
    }

    /**
     * @param string         $abstract
     * @param string|Closure $concrete
     * @param array          $parameters
     */
    public function addAlias($abstract, $concrete, array $parameters = array())
    {
        $abstract = ltrim($abstract, '\\');
        if (is_string($concrete)) {
            $concrete = ltrim($concrete, '\\');
            if (!empty($parameters)) {
                $this->constructorArguments[$concrete] = $parameters;
            }
            if ($abstract !== $concrete) {
                $this->aliases[$abstract] = $concrete;
            }
        } else {
            $this->aliases[$abstract] = $concrete;
        }
    }

    /**
     * @param string $concrete
     */
    public function addConstructorArguments($concrete /*, ...$parameters */)
    {
        $concrete = ltrim($concrete, '\\');

        $this->constructorArguments[$concrete] = array_slice(func_get_args(), 1);
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
            throw new OutOfBoundsException(sprintf('%s is not registered.', $abstract));
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
        $concrete = $this->findMostConcreteDefinition($abstract);
        if (!is_string($concrete)) {
            return array();
        }
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
        $concrete = $this->findMostConcreteDefinition($abstract ? : get_class($object));
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
        // try to find the constructor arguments for the most concrete definition
        $concrete             = $this->findMostConcreteDefinition($abstract);
        $registeredParameters = $this->getConstructorArguments($concrete);

        $key = is_string($concrete) ? $concrete : ltrim($abstract, '\\');

        if (isset($this->objects[$key]) && !$forceNew) {
            return $this->objects[$key];
        }

        $parameters = $parameters + $registeredParameters;

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
        if (!isset($this->callbacks[$concrete])) {
            return;
        }
        foreach ($this->callbacks[$concrete] as $callback) {
            $callback($object, $this);
        }
    }

    /**
     * @param $class
     *
     * @return string
     */
    private function findMostConcreteDefinition($class)
    {
        if (!is_string($class)) {
            return $class;
        }
        $classVarIsString = true;
        $class            = ltrim($class, '\\');
        while ($classVarIsString && isset($this->aliases[$class])) {
            $class            = $this->aliases[$class];
            $classVarIsString = is_string($class);
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
            throw new InvalidArgumentException(sprintf('Class %s is not instantiable.', $concrete));
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete;
        }

        $constructorArgs = $constructor->getParameters();
        if (!empty($parameters)) {
            $constructorArgs = array_diff_key($constructorArgs, $parameters);
        }
        $resolvedArgs = $this->resolveDependencies($constructorArgs);
        $arguments    = $this->linkResolver->resolveReferences($resolvedArgs + $parameters);

        ksort($arguments);

        return $reflector->newInstanceArgs($arguments);
    }

    /**
     * @param ReflectionParameter[] $dependencies
     *
     * @return array
     */
    private function resolveDependencies(array $dependencies)
    {
        $resolved = array();
        foreach ($dependencies as $k => $dependency) {
            $class = $dependency->getClass();
            if ($class === null) {
                // primitive type
                $resolved[$k] = $this->resolvePrimitiveParameter($dependency);
            } else {
                $resolved[$k] = $this->resolveClassParameter($class, $dependency);
            }
        }

        return $resolved;
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
        $message       = sprintf(
            'Parameter %s is not supplied for %s::%s.',
            $parameterName,
            $class,
            $method
        );
        throw new OutOfBoundsException($message);
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
