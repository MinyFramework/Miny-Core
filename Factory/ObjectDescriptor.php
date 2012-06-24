<?php

/**
 * This file is part of the Miny framework.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version accepted by the author in accordance with section
 * 14 of the GNU General Public License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   Miny/Factory
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 *
 */

namespace Miny\Factory;

/**
 * ObjectDescriptor class
 * Responsible for statically descripting objects and their dependencies.
 * For usage, see @link http://prominence.bugadani.hu/packages/factory
 *
 * @author  Dániel Buga
 */
class ObjectDescriptor
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
    private $args;

    /**
     * Methods to call upon instantiation.
     * @access private
     * @var array
     */
    private $methods;

    /**
     * Properties to set upon instantaiation.
     * @access private
     * @var array
     */
    private $properties;

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
    public function __construct($classname, array $ctor_arguments = NULL,
            $singleton = true)
    {
        if (!is_string($classname)) {
            throw new \InvalidArgumentException('Classname must be string.');
        }
        $this->classname = $classname;
        $this->singleton = $singleton;
        $this->args = $ctor_arguments;
        $this->methods = array();
        $this->properties = array();
    }

    /**
     * Sets constructor arguments.
     *
     * @param array $arg
     * @return ObjectDescriptor
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
     * @return ObjectDescriptor
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
     * @return ObjectDescriptor
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