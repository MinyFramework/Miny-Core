<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Factory;

class Container
{
    /**
     * @var (string|\Closure)[]
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
     * @var LinkResolver
     */
    private $linkResolver;

    /**
     * @var callable[]
     */
    private $callbacks = array();

    /**
     * @param LinkResolver $resolver
     */
    public function __construct(LinkResolver $resolver)
    {
        $this->linkResolver = $resolver;

        $this->objects = array(
            __CLASS__            => $this,
            get_class($resolver) => $resolver
        );
    }

    /**
     * @param string         $abstract
     * @param string|\Closure $concrete
     */
    public function addAlias($abstract, $concrete)
    {
        //Strip all leading backslashes
        if ($abstract[0] === '\\') {
            $abstract = substr($abstract, 1);
        }
        if (is_string($concrete) && $concrete[0] === '\\') {
            //Strip all leading backslashes if $concrete is a class name
            $concrete = substr($concrete, 1);
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
            $this->constructorArguments[$concrete] = array($position => $argument);
        } else {
            $this->constructorArguments[$concrete][$position] = $argument;
        }
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
     * @throws \InvalidArgumentException
     */
    public function addCallback($concrete, $callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('$callback is not callable.');
        }
        //Strip all leading backslashes
        if ($concrete[0] === '\\') {
            $concrete = substr($concrete, 1);
        }

        if (!isset($this->callbacks[$concrete])) {
            $this->callbacks[$concrete] = array($callback);
        } else {
            $this->callbacks[$concrete][] = $callback;
        }
    }

    /**
     * @param $abstract
     *
     * @return string|\Closure
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
            $this->constructorArguments[$concrete] = array();
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
        }
        //Strip all leading backslashes
        if ($abstract[0] === '\\') {
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
    public function get($abstract, array $parameters = array(), $forceNew = false)
    {
        //Strip all leading backslashes
        if ($abstract[0] === '\\') {
            $abstract = substr($abstract, 1);
        }

        //Try to find the constructor arguments for the most concrete definition
        $concrete = $this->findMostConcreteDefinition($abstract);

        //If there are predefined constructor arguments, merge them with out parameter array
        if (isset($this->constructorArguments[$concrete])) {
            $parameters = $parameters + $this->constructorArguments[$concrete];
        }

        if (isset($this->objects[$concrete]) && !$forceNew) {
            //Return the stored instance if a new one is not forced
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
        $visited = array();

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
    private function instantiate($class, array $parameters = array())
    {
        if ($class instanceof \Closure) {
            return $class($this, $parameters);
        }
        $reflector = new \ReflectionClass($class);

        if (!$reflector->isInstantiable()) {
            //Try to give a descriptive exception message
            if ($reflector->isAbstract()) {
                throw new \InvalidArgumentException("Class {$class} is abstract and can not be instantiated.");
            } elseif ($reflector->isInterface()) {
                throw new \InvalidArgumentException("{$class} is an interface can not be instantiated.");
            } else {
                throw new \InvalidArgumentException("Class {$class} can not be instantiated.");
            }
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            //Since the class has no constructor, it can not be instantiated with ReflectionClass
            return new $class;
        }

        $constructorArgs = $constructor->getParameters();

        if (empty($constructorArgs)) {
            //If the class has no constructor arguments, it is cheaper to instantiate it directly
            //Although this has the downside of disallowing variable sized argument lists if
            //no arguments are required by the constructor signature.
            return new $class;
        }

        $arguments = $this->resolveDependencies($constructorArgs, $parameters);
        if (!empty($parameters)) {
            //We need to sort the arguments because ReflectionClass passes them by order, not by index
            ksort($arguments);
        }

        return $reflector->newInstanceArgs($arguments);
    }

    /**
     * @param \ReflectionParameter[] $dependencies
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
    private function resolveClassParameter(\ReflectionClass $class, \ReflectionParameter $dependency)
    {
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
