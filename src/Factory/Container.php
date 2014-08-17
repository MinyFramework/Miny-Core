<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Factory;

class Container
{
    /**
     * @var (string|callable)[]
     */
    private $aliases = [];

    /**
     * @var array
     */
    private $constructorArguments = [];

    /**
     * @var array
     */
    private $objects;

    /**
     * @var LinkResolver
     */
    private $linkResolver;

    /**
     * @var callable[]
     */
    private $callbacks = [];

    /**
     * @param LinkResolver $resolver
     */
    public function __construct(LinkResolver $resolver)
    {
        $this->linkResolver = $resolver;

        $this->objects = [
            __CLASS__            => $this,
            get_class($resolver) => $resolver
        ];
    }

    /**
     * @param string          $abstract
     * @param string|callable $concrete
     *
     * @throws \InvalidArgumentException
     */
    public function addAlias($abstract, $concrete)
    {
        if (is_string($concrete)) {
            if ($concrete[0] === '\\') {
                //Strip all leading backslashes if $concrete is a class name
                $concrete = substr($concrete, 1);
            }
        } elseif (!is_callable($concrete)) {
            throw new \InvalidArgumentException('The alias must either be a class name or a callable');
        }
        //Strip all leading backslashes
        if ($abstract[0] === '\\') {
            $abstract = substr($abstract, 1);
        }
        $this->aliases[$abstract] = $concrete;
    }

    /**
     * @param string $concrete
     * @param        mixed ...  $arguments
     */
    public function addConstructorArguments($concrete /*, ...$arguments */)
    {
        $this->setConstructorArguments($concrete, array_slice(func_get_args(), 1));
    }

    /**
     * @param $concrete
     * @param $position
     * @param $argument
     */
    public function setConstructorArgument($concrete, $position, $argument)
    {
        //Strip all leading backslashes
        if ($concrete[0] === '\\') {
            $concrete = substr($concrete, 1);
        }

        if (!isset($this->constructorArguments[$concrete])) {
            $this->constructorArguments[$concrete] = [];
        }
        $this->constructorArguments[$concrete][$position] = $argument;
    }

    /**
     * @param       $concrete
     * @param array $arguments
     */
    public function setConstructorArguments($concrete, array $arguments)
    {
        //Strip all leading backslashes
        if ($concrete[0] === '\\') {
            $concrete = substr($concrete, 1);
        }

        // filter the nulls and set the arguments
        $this->constructorArguments[$concrete] = array_filter(
            $arguments,
            function ($item) {
                return $item !== null;
            }
        );
    }

    /**
     * @param $concrete
     * @param $callback
     *
     * @throws \InvalidArgumentException
     */
    public function addCallback($concrete, callable $callback)
    {
        //Strip all leading backslashes
        if ($concrete[0] === '\\') {
            $concrete = substr($concrete, 1);
        }

        if (!isset($this->callbacks[$concrete])) {
            $this->callbacks[$concrete] = [];
        }
        $this->callbacks[$concrete][] = $callback;
    }

    /**
     * @param $abstract
     *
     * @return string|callable
     * @throws \OutOfBoundsException
     */
    public function getAlias($abstract)
    {
        //Strip all leading backslashes
        if ($abstract[0] === '\\') {
            $abstract = substr($abstract, 1);
        }

        if (!isset($this->aliases[$abstract])) {
            throw new \OutOfBoundsException("{$abstract} is not registered.");
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
        //Strip all leading backslashes
        if ($abstract[0] === '\\') {
            $abstract = substr($abstract, 1);
        }

        $concrete = $this->findMostConcreteDefinition($abstract);
        if (!isset($this->constructorArguments[$concrete])) {
            $this->constructorArguments[$concrete] = [];
        }

        return $this->constructorArguments[$concrete];
    }

    /**
     * @param object $object
     * @param string $abstract
     *
     * @return object|false
     * @throws \InvalidArgumentException
     */
    public function setInstance($object, $abstract = null)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('$object must be an object');
        }
        if ($abstract === null) {
            //If $abstract is not specified, use $objects class
            $abstract = get_class($object);
        } elseif ($abstract[0] === '\\') {
            //Strip all leading backslashes
            $abstract = substr($abstract, 1);
        }
        $concrete = $this->findMostConcreteDefinition($abstract);
        if (isset($this->objects[$concrete])) {
            //If this class already has an instance, we'll return the old object
            $old = $this->objects[$concrete];
        } else {
            $old = false;
        }
        //Store the object
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
    public function get($abstract, array $parameters = [], $forceNew = false)
    {
        //Strip all leading backslashes
        if ($abstract[0] === '\\') {
            $abstract = substr($abstract, 1);
        }

        //Try to find the constructor arguments for the most concrete definition
        $concrete = $this->findMostConcreteDefinition($abstract);

        if (isset($this->objects[$concrete]) && !$forceNew) {
            //Return the stored instance if a new one is not forced
            return $this->objects[$concrete];
        }

        //If there are predefined constructor arguments, merge them with out parameter array
        if (isset($this->constructorArguments[$concrete])) {
            $parameters = $parameters + $this->constructorArguments[$concrete];
        }

        $key = $concrete;
        if (isset($this->aliases[$concrete])) {
            // $concrete is not a string here
            $concrete = $this->aliases[$concrete];
        }
        $object = $this->instantiate($concrete, $parameters);
        $this->callCallbacks($key, $object);

        if (!$forceNew) {
            //If the object is forced to be new, it will not be stored
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
     * @throws \RuntimeException
     * @return string
     */
    private function findMostConcreteDefinition($class)
    {
        //This array holds the classes that were processed
        $visited = [];

        //One can specify an alias of an alias (or rather, override a class definition by an other)
        //so we have to traverse this structure
        while (isset($this->aliases[$class]) && is_string($this->aliases[$class])) {
            $class = $this->aliases[$class];
            if (isset($visited[$class])) {
                //If $class was already visited, we have a circular alias situation that can't be resolved
                throw new \RuntimeException("Circular aliases detected for class {$class}.");
            }
            $visited[$class] = true;
        }

        //At this point, $class is either the last alias (it is the most concrete class name registered)
        //or it is not a string. This can happen when a class has a Closure initializer.
        return $class;
    }

    /**
     * @param string $class
     * @param array  $parameters
     *
     * @return object
     * @throws \InvalidArgumentException
     */
    private function instantiate($class, array $parameters = [])
    {
        if (is_callable($class)) {
            return $class($this, $parameters);
        }
        $reflector = new \ReflectionClass($class);

        if (!$reflector->isInstantiable()) {
            //Try to give a descriptive exception message
            if ($reflector->isAbstract()) {
                throw new \InvalidArgumentException("Class {$class} is abstract and can not be instantiated.");
            }
            if ($reflector->isInterface()) {
                throw new \InvalidArgumentException("{$class} is an interface and can not be instantiated.");
            }
            throw new \InvalidArgumentException("Class {$class} can not be instantiated.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            //Since the class has no constructor, it can not be instantiated with ReflectionClass

            //Also, if the class has no constructor arguments, it is cheaper to instantiate it directly
            //Although this has the downside of disallowing variable sized argument lists if
            //no arguments are required by the constructor signature.
            return new $class;
        }

        $constructorArgs = $constructor->getParameters();
        $arguments       = $this->resolveDependencies($constructorArgs, $parameters);

        if (!empty($parameters)) {
            //We need to sort the arguments because ReflectionClass passes them by order, not by index
            ksort($arguments);
        }

        return $reflector->newInstanceArgs($arguments);
    }

    private function resolveDependencies(array $dependencies, array $resolved)
    {
        $dependencies = array_diff_key($dependencies, $resolved);
        foreach ($dependencies as $k => $dependency) {
            /** @var $dependency \ReflectionParameter */
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
     * @param \ReflectionParameter $dependency
     *
     * @return mixed
     * @throws \OutOfBoundsException
     */
    private function resolvePrimitiveParameter(\ReflectionParameter $dependency)
    {
        if ($dependency->isDefaultValueAvailable()) {
            return $dependency->getDefaultValue();
        }

        $class         = $dependency->getDeclaringClass()->getName();
        $method        = $dependency->getDeclaringFunction()->getName();
        $parameterName = $dependency->getName();

        throw new \OutOfBoundsException("Parameter {$parameterName} is not supplied for {$class}::{$method}.");
    }

    /**
     * @param \ReflectionClass     $class
     * @param \ReflectionParameter $dependency
     *
     * @throws \InvalidArgumentException
     * @return mixed|object
     */
    private function resolveClassParameter(
        \ReflectionClass $class,
        \ReflectionParameter $dependency
    ) {
        try {
            return $this->get($class->getName());
        } catch (\InvalidArgumentException $e) {
            if (!$dependency->isDefaultValueAvailable()) {
                throw $e;
            }
        }

        return $dependency->getDefaultValue();
    }
}
