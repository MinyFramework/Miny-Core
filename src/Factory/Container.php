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
use ReflectionException;
use ReflectionParameter;

class Container
{
    /**
     * @var array
     */
    private $aliases;

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
    private $callbacks;

    /**
     * @param AbstractLinkResolver $resolver
     */
    public function __construct(AbstractLinkResolver $resolver = null)
    {
        if (!$resolver) {
            $resolver = new NullResolver();
        }
        $this->linkResolver = $resolver;
        $this->objects      = array();
        $this->aliases      = array();
        $this->callbacks    = array();

        $this->setInstance($this, __CLASS__);
        $this->setInstance($resolver);
    }

    /**
     * @param string $abstract
     * @param string $concrete
     * @param array  $parameters
     */
    public function addAlias($abstract, $concrete = null, array $parameters = array())
    {
        $abstract                 = ltrim($abstract, '\\');
        $this->aliases[$abstract] = array($concrete ? : $abstract, $parameters);
    }

    /**
     * @param string $concrete
     */
    public function addConstructorArguments($concrete /*, ...$parameters */)
    {
        if (func_num_args() === 1) {
            return;
        }
        $parameters = array_slice(func_get_args(), 1);
        $this->addAlias($concrete, null, $parameters);
    }

    public function addCallback($concrete, $callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('$callback is not callable.');
        }
        $concrete = ltrim($concrete, '\\');
        if (!isset($this->callbacks[$concrete])) {
            $this->callbacks[$concrete] = array();
        }
        $this->callbacks[$concrete][] = $callback;
    }

    /**
     * @param $abstract
     *
     * @throws OutOfBoundsException
     * @return array
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
     * @param object $object
     * @param string $name
     *
     * @return object|false
     * @throws InvalidArgumentException
     */
    public function setInstance($object, $name = null)
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException('$object must be an object');
        }
        if ($name === null) {
            $name = get_class($object);
        }
        if (isset($this->aliases[$name])) {
            list($name) = $this->aliases[$name];
        }
        if (isset($this->objects[$name])) {
            $old = $this->objects[$name];
        } else {
            $old = false;
        }
        $this->objects[$name] = $object;

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
        if (isset($this->aliases[$abstract])) {
            do {
                list($concrete, $registeredParameters) = $this->aliases[$abstract];
                if ($concrete !== $abstract) {
                    $abstract = $concrete;
                    $abstract = ltrim($abstract, '\\');
                } else {
                    break;
                }
            } while (isset($this->aliases[$abstract]));
        } else {
            $concrete             = $abstract;
            $registeredParameters = array();
        }
        if (isset($this->objects[$concrete]) && !$forceNew) {
            return $this->objects[$concrete];
        }

        $parameters = $parameters + $registeredParameters;

        $object = $this->instantiate($concrete, $parameters);

        if (isset($this->callbacks[$concrete])) {
            foreach ($this->callbacks[$concrete] as $callback) {
                $callback($object, $this);
            }
        }

        if (!$forceNew) {
            $this->objects[$concrete] = $object;
        }

        return $object;
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
        $unsuppliedArgs  = array_diff_key($constructorArgs, $parameters);
        $resolvedArgs    = $this->resolveDependencies($unsuppliedArgs);
        $arguments       = $resolvedArgs + $parameters;
        $arguments       = $this->linkResolver->resolveReferences($arguments);

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
                $resolved[$k] = $this->resolveClassParameter($dependency);
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
        if (!$dependency->isDefaultValueAvailable()) {
            $class         = $dependency->getDeclaringClass()->getName();
            $method        = $dependency->getDeclaringFunction()->getName();
            $parameterName = $dependency->getName();
            $message       = sprintf('Parameter %s is not supplied for %s::%s.', $parameterName, $class, $method);
            throw new OutOfBoundsException($message);
        }

        return $dependency->getDefaultValue();
    }

    /**
     * @param ReflectionParameter $dependency
     *
     * @return mixed|object
     * @throws ReflectionException
     */
    private function resolveClassParameter(ReflectionParameter $dependency)
    {
        $class = $dependency->getClass();
        try {
            return $this->get($class->getName());
        } catch (ReflectionException $e) {
            if ($dependency->isDefaultValueAvailable()) {
                return $dependency->getDefaultValue();
            } else {
                throw $e;
            }
        }
    }
}
