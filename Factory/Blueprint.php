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
 * Responsible for statically descripting objects and their dependencies.
 *
 * @author  Dániel Buga
 */
class Blueprint
{
    /**
     * @see ObjectDescriptor::isSingleton()
     * @access private
     * @var boolean
     */
    private $singleton;

    /**
     * The classname of the descripted object.
     * @access private
     * @var string
     */
    private $classname;

    /**
     * The constructor arguments.
     * @access private
     * @var array
     */
    private $args = array();

    /**
     * Methods to call upon instantiation.
     * @access private
     * @var array
     */
    private $methods = array();

    /**
     * Properties to set upon instantaiation.
     * @access private
     * @var array
     */
    private $properties = array();

    /**
     * @access private
     * @var string
     */
    private $parent;

    /**
     *
     * @param string $classname The classname of the described object.
     * @param array $ctor_arguments The parameters for object constructor.
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
     * @param array $arg
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
     * @return ObjectDescriptor
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
     * @param array $arguments
     * @return Blueprint
     */
    public function addMethodCall()
    {
        $arguments = func_get_args();
        $method = array_shift($arguments);
        $this->methods[] = array($method, $arguments);
        return $this;
    }

    /**
     * Sets a value to be set as property $name.
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
     * @return boolean Wether the object has a parent or not.
     */
    public function hasParent()
    {
        return $this->parent !== NULL;
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