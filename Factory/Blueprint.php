<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Factory;

use InvalidArgumentException;

/**
 * Blueprint class
 * Responsible for statically describing objects and their dependencies.
 *
 * @author Dániel Buga
 */
class Blueprint
{
    /**
     * @var boolean
     */
    private $singleton;

    /**
     * @var string
     */
    private $classname;

    /**
     * @var array
     */
    private $args = array();

    /**
     * @var array
     */
    private $methods = array();

    /**
     * @var array
     */
    private $properties = array();

    /**
     * @var string
     */
    private $parent;

    /**
     * @param string $classname The classname of the described object.
     * @param boolean $singleton Indicates, whether the object is a singleton.
     */
    public function __construct($classname, $singleton = true)
    {
        if (!is_string($classname)) {
            throw new InvalidArgumentException('Classname must be string.');
        }
        $this->classname = $classname;
        $this->singleton = $singleton;
    }

    /**
     * Sets constructor arguments.
     *
     * @return Blueprint
     */
    public function setArguments()
    {
        $this->args = func_get_args();
        return $this;
    }

    /**
     * Sets parent object's name.
     *
     * @param string $parent
     * @return Blueprint
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * Sets a method with its parameters to be called upon instantiation.
     *
     * @param string $method
     * @return Blueprint
     */
    public function addMethodCall($method)
    {
        $arguments       = array_slice(func_get_args(), 1);
        $this->methods[] = array($method, $arguments);
        return $this;
    }

    /**
     * Sets a value for property given in $name.
     *
     * @param string $name
     * @param mixed $value
     * @return Blueprint
     */
    public function setProperty($name, $value)
    {
        $this->properties[$name] = $value;
        return $this;
    }

    /**
     * Gets the class name to instantiate.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->classname;
    }

    /**
     * isSingleton returns with a boolean that indicates
     * whether the object should only be instantiated once.
     *
     * @return boolean
     */
    public function isSingleton()
    {
        return $this->singleton;
    }

    /**
     * Gets the constructor parameters.
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->args;
    }

    /**
     * Gets the method names and parameters which
     * should be called on the object.
     *
     * @return array
     */
    public function getMethodCalls()
    {
        return $this->methods;
    }

    /**
     * Gets the parameters which should be injected as properties.
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return boolean Whether the object has a parent or not.
     */
    public function hasParent()
    {
        return $this->parent !== null;
    }

    /**
     * Gets the parent object's name.
     *
     * @return string
     */
    public function getParent()
    {
        return $this->parent;
    }
}
