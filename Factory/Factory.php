<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Factory;

use ArrayAccess;
use InvalidArgumentException;
use Miny\Utils\Utils;
use OutOfBoundsException;

/**
 * Factory class
 * Responsible for storing Blueprints and their dependencies, parameters.
 * Factory instantiates stored objects on demand and injects them with specified
 * dependencies.
 *
 * @author  Dániel Buga
 */
class Factory implements ArrayAccess
{
    /**
     * @var object[]
     */
    protected $objects = array();

    /**
     * @var Blueprint[]
     */
    protected $blueprints = array();

    /**
     * @var ParameterContainer
     */
    protected $parameters;

    /**
     * @var string[]
     */
    protected $aliasses = array();

    /**
     * @param array $params Initial list of parameters to be stored.
     */
    public function __construct(array $params = array())
    {
        $this->parameters = new ParameterContainer($params);
        $this->setInstance('factory', $this);
    }

    /**
     * Retrieves parameter container.
     *
     * @return ParameterContainer
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param string $alias
     * @param string $target
     */
    public function addAlias($alias, $target)
    {
        $this->aliasses[$alias] = $target;
    }

    /**
     * @param string $alias
     * @return string
     */
    public function getAlias($alias)
    {
        if (isset($this->aliasses[$alias])) {
            $alias = $this->aliasses[$alias];
        }
        return $alias;
    }

    /**
     * Creates a Blueprint for $classname and registers it with $alias.
     *
     * @param string $alias
     * @param string $classname
     * @param boolean $singleton
     * @return Blueprint
     */
    public function add($alias, $classname, $singleton = true)
    {
        return $this->register($alias, new Blueprint($classname, $singleton));
    }

    /**
     * Registers a Blueprint with the given alias.
     * Unsets any existing instances for the given alias.
     *
     * @param string $alias
     * @param Blueprint $object
     * @return Blueprint
     */
    public function register($alias, Blueprint $object)
    {
        $this->blueprints[$alias] = $object;
        unset($this->objects[$alias]);
        return $object;
    }

    /**
     * Adds an object instance.
     *
     * @see Factory::create()
     * @param string $alias
     * @param object $object
     */
    public function setInstance($alias, $object)
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException('Factory::setInstance needs an object for alias ' . $alias);
        }
        $this->objects[$alias] = $object;
    }

    /**
     * @param string $alias
     * @param object $object
     */
    public function __set($alias, $object)
    {
        if (is_string($object)) {
            $this->add($alias, $object);
        } elseif ($object instanceof Blueprint) {
            $this->register($alias, $object);
        } else {
            $this->setInstance($alias, $object);
        }
    }

    /**
     * Gets the Blueprint stored for $alias.
     *
     * @param string $alias
     * @return Blueprint
     * @throws OutOfBoundsException
     */
    public function getBlueprint($alias)
    {
        if (!isset($this->blueprints[$alias])) {
            throw new OutOfBoundsException('Blueprint not found: ' . $alias);
        }
        return $this->blueprints[$alias];
    }

    /**
     * Retrieves all stored Blueprint objects.
     *
     * @return Blueprint[]
     */
    public function getBlueprints()
    {
        return $this->blueprints;
    }

    /**
     * Returns with the object stored under $alias.
     * The method creates the object instance if needed.
     *
     * @param string $alias
     * @return object
     */
    public function __get($alias)
    {
        $alias = $this->getAlias($alias);
        if (isset($this->objects[$alias])) {
            return $this->objects[$alias];
        }

        $descriptor = $this->getBlueprint($alias);
        $obj        = $this->instantiate($descriptor);

        if ($descriptor->isSingleton()) {
            $this->setInstance($alias, $obj);
        }

        return $this->injectDependencies($obj, $descriptor);
    }

    /**
     * @param object $object
     * @param Blueprint $descriptor
     */
    private function injectDependencies($object, Blueprint $descriptor)
    {
        if ($descriptor->hasParent()) {
            $parent = $this->getBlueprint($descriptor->getParent());
            $this->injectDependencies($object, $parent);
        }
        foreach ($descriptor->getProperties() as $name => $value) {
            $object->$name = $this->resolveReferences($value);
        }
        foreach ($descriptor->getMethodCalls() as $method) {
            list($name, $args) = $method;
            $arguments = $this->resolveReferences($args);
            call_user_func_array(array($object, $name), $arguments);
        }
        return $object;
    }

    /**
     * @param Blueprint $descriptor
     * @return object
     * @throws InvalidArgumentException
     */
    private function instantiate(Blueprint $descriptor)
    {
        $class = $descriptor->getClassName();
        if ($class[0] == '@') {
            $class = $this->offsetGet(substr($class, 1));
        }

        if (!class_exists($class)) {
            throw new InvalidArgumentException('Class not found: ' . $class);
        }
        $args      = $descriptor->getArguments();
        $arguments = $this->resolveReferences($args);

        return Utils::instantiate($class, $arguments);
    }

    /**
     * Resolves parameter references recursively.
     *
     * @param mixed $var
     * @return mixed
     */
    private function resolveReferences($var)
    {
        //substitute values in arrays, too
        if (is_array($var)) {
            foreach ($var as $key => $value) {
                $var[$key] = $this->resolveReferences($value);
            }
            return $var;
        }

        //if the parameter is a Blueprint, instantiate and inject it
        if ($var instanceof Blueprint) {
            $object = $this->instantiate($var);
            return $this->injectDependencies($object, $var);
        }

        //direct injection for non-string values
        if (!is_string($var) || strlen($var) <= 1) {
            return $var;
        }

        //Resolve any links in $var
        $var = $this->parameters->resolveLinks($var);

        //see if $var is a reference to something
        $str = substr($var, 1);
        switch ($var[0]) {
            case '@':
                //parameter
                $key = $this->parameters[$str];
                $var = $this->resolveReferences($key);
                break;

            case '&':
                //object or method call. Basically this is
                //$factory->create('object')->method(parameters);
                $key = $this->getObjectParameter($str);
                $var = $this->resolveReferences($key);
                break;

            case '*':
                //callback
                if (strpos($var, '::') !== false) {
                    list($obj_name, $method) = explode('::', $str, 2);
                    $var = array($this->__get($obj_name), $method);
                }
                break;

            case '\\':
                //remove backslash from escaped characters
                $escaped = array('\\', '*', '&', '@');
                if (in_array($var[1], $escaped)) {
                    $var = $str;
                }
                break;
        }
        return $var;
    }

    /**
     * @param string $str
     * @return mixed
     * @throw InvalidArgumentException
     */
    private function getObjectParameter($str)
    {
        if (($pos = strpos($str, '::')) !== false) {
            //method parameters are separated by ::
            //TOOD: use different separator for method names and arguments, support for nested method calls
            $arr      = explode('::', $str);
            $obj_name = array_shift($arr);
            $method   = array_shift($arr);
            $object   = $this->__get($obj_name);

            $callback = array($object, $method);
            if (!is_callable($callback)) {
                $method = sprintf('Class "%s" does not have a method "%s"', $obj_name, $method);
                throw new InvalidArgumentException($method);
            }
            return call_user_func_array($callback, $this->parameters->resolveLinks($arr));
        } elseif (($pos = strpos($str, '->')) !== false) {
            list($obj_name, $property) = explode('->', $str, 2);
            return $this->__get($obj_name)->$property;
        } else {
            return $this->__get($str);
        }
    }

    public function offsetExists($offset)
    {
        return $this->parameters->offsetExists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->resolveReferences($this->parameters->offsetGet($offset));
    }

    public function offsetSet($offset, $value)
    {
        $this->parameters->offsetSet($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->parameters->offsetUnset($offset);
    }
}
