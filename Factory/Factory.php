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
use OutOfBoundsException;
use ReflectionClass;

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
    protected $objects = array();
    protected $blueprints = array();
    protected $parameters = array();
    protected $aliasses = array();

    /**
     *
     * @param array $params Initial list of parameters to be stored.
     */
    public function __construct(array $params = array())
    {
        $this->setParameters($params);
        $this->setInstance('factory', $this);
    }

    /**
     *
     * @param string $alias
     * @param string $target
     */
    public function addAlias($alias, $target)
    {
        $this->aliasses[$alias] = $target;
    }

    /**
     *
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
     * @return \Miny\Factory\Blueprint
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
     * @param \Miny\Factory\Blueprint $object
     * @return \Miny\Factory\Blueprint
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
     *
     * @param type $alias
     * @param object $object
     */
    public function __set($alias, $object)
    {
        if ($object instanceof Blueprint) {
            $this->register($alias, $object);
        } else {
            $this->setInstance($alias, $object);
        }
    }

    /**
     * Gets the Blueprint stored for $alias.
     *
     * @param string $alias
     * @return \Miny\Factory\Blueprint
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
     * @return array
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
        $obj = $this->instantiate($descriptor);

        if ($descriptor->isSingleton()) {
            $this->setInstance($alias, $obj);
        }

        $this->injectDependencies($obj, $descriptor);
        return $obj;
    }

    private function injectDependencies($object, Blueprint $descriptor)
    {
        if ($descriptor->hasParent()) {
            $parent = $this->getBlueprint($descriptor->getParent());
            $this->injectDependencies($object, $parent);
        }
        foreach ($descriptor->getProperties() as $name => $value) {
            $object->$name = $this->getValue($value);
        }
        foreach ($descriptor->getMethodCalls() as $method) {
            list($name, $args) = $method;
            $args = array_map(array($this, 'getValue'), $args);
            call_user_func_array(array($object, $name), $args);
        }
    }

    private function instantiate(Blueprint $descriptor)
    {
        $class = $descriptor->getClassName();
        if ($class[0] == '@') {
            $class = $this->offsetGet(substr($class, 1));
        }

        if (!class_exists($class)) {
            throw new InvalidArgumentException('Class not found: ' . $class);
        }
        $args = $descriptor->getArguments();
        switch (count($args)) {
            case 0:
                $obj = new $class;
                break;
            case 1:
                $arg = $this->getValue(current($args));
                $obj = new $class($arg);
                break;
            default:
                $ref = new ReflectionClass($class);
                $args = array_map(array($this, 'getValue'), $args);
                $obj = $ref->newInstanceArgs($args);
        }
        return $obj;
    }

    /**
     * Resolves parameter references recursively.
     *
     * @param mixed $var
     * @return mixed
     */
    public function getValue($var)
    {
        //substitute values in arrays, too
        if (is_array($var)) {
            foreach ($var as $key => $value) {
                $var[$key] = $this->getValue($value);
            }
        }

        //if the parameter is a Blueprint, instantiate and inject it
        if ($var instanceof Blueprint) {
            $object = $this->instantiate($var);
            $this->injectDependencies($object, $var);
            return $object;
        }

        //direct injection for non-string values
        if (!is_string($var) || strlen($var) <= 1) {
            return $var;
        }

        //see if $var is a reference to something
        $str = substr($var, 1);
        switch ($var[0]) {
            case '@'://param
                $var = $this->offsetGet($str);
                break;
            case '&':
                //object or method call. Basically this is
                //$factory->create('object')->method(parameters);
                $var = $this->getObjectParameter($str);
                break;
            case '*'://callback
                if (strpos($var, '::') !== false) {
                    list($obj_name, $method) = explode('::', $str);
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

    private function getObjectParameter($str)
    {
        if (($pos = strpos($str, '::')) !== false) {
            //method parameters are separated by ::
            //TOOD: use different separator for method names and arguments, support for nested method calls
            $arr = explode('::', $str);
            $obj_name = array_shift($arr);
            $method = array_shift($arr);
            $object = $this->__get($obj_name);
            return call_user_func_array(array($object, $method), $this->resolveLinks($arr));
        } elseif (($pos = strpos($str, '->')) !== false) {
            list($obj_name, $property) = explode('->', $str, 2);
            $object = $this->__get($obj_name);
            return $object->$property;
        } else {
            return $this->__get($str);
        }
    }

    private function merge($array1, $array2)
    {
        foreach ($array2 as $key => $value) {
            if (isset($array1[$key]) && is_array($array1[$key]) && is_array($value)) {
                $array1[$key] = $this->merge($array1[$key], $value);
            } else {
                $array1[$key] = $value;
            }
        }
        return $array1;
    }

    /**
     * Stores an array of parameters.
     * Parameters must be defined as name => value
     *
     * @param array $parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $this->merge($this->parameters, $parameters);
    }

    /**
     * Retrieves all stored parameters.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Retrieves all stored parameters with their links resolved.
     *
     * @return array
     */
    public function getResolvedParameters()
    {
        return $this->resolveLinks($this->parameters);
    }

    /**
     * Processes a parameter string, which specifies a stored parameter.
     * You can use colons (:) to reference an element of an array within an array ...
     *
     * @param string $key The parameter to get.
     * @return mixed The parameter value
     * @throws OutOfBoundsException
     */
    public function offsetGet($key)
    {
        if (strpos($key, ':') !== false) {
            $return = $this->parameters;
            foreach (explode(':', $key) as $k) {
                if (!array_key_exists($k, $return)) {
                    throw new OutOfBoundsException('Parameter not set: ' . $key);
                }
                $return = $return[$k];
            }
        } elseif (isset($this->parameters[$key])) {
            $return = $this->parameters[$key];
        } else {
            throw new OutOfBoundsException('Parameter not set: ' . $key);
        }
        return $this->resolveLinks($return);
    }

    private function resolveLinks($value)
    {
        if (is_array($value)) {
            $return = array();
            foreach ($value as $k => $v) {
                $k = $this->resolveLinks($k);
                $return[$k] = $this->resolveLinks($v);
            }
            return $return;
        }
        if (is_string($value)) {
            return preg_replace_callback('/(?<!\\\){@(.*?)}/', array($this, 'resolveLinksCallback'), $value);
        }
        return $value;
    }

    private function resolveLinksCallback($matches)
    {
        return $this->offsetExists($matches[1]) ? $this->offsetGet($matches[1]) : $matches[0];
    }

    /**
     * Stores a parameter for $key serving as key.
     *
     * @param string $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        if (strpos($key, ':') !== false) {
            $arr = & $this->parameters;
            foreach (explode(':', $key) as $k) {
                if (!array_key_exists($k, $arr)) {
                    $arr[$k] = array();
                }
                $arr = & $arr[$k];
            }
            $arr = $value;
        } else {
            $this->parameters[$key] = $value;
        }
    }

    /**
     * Removes the parameter specified with $key.
     *
     * @param string $key
     */
    public function offsetUnset($key)
    {
        if (strpos($key, ':') !== false) {
            $parts = explode(':', $key);
            $last = count($parts) - 1;
            $arr = & $this->parameters;
            foreach ($parts as $i => $k) {
                if (!array_key_exists($k, $arr)) {
                    return;
                }
                if ($i !== $last) {
                    $arr = & $arr[$k];
                } else {
                    unset($arr[$k]);
                }
            }
        } else {
            unset($this->parameters[$key]);
        }
    }

    /**
     * Indicates whether the parameter specified with $key is set.
     *
     * @param string $key
     * @return boolean
     */
    public function offsetExists($key)
    {
        if (strpos($key, ':') !== false) {
            $arr = $this->parameters;
            foreach (explode(':', $key) as $k) {
                if (!array_key_exists($k, $arr)) {
                    return false;
                }
                $arr = $arr[$k];
            }
            return true;
        } else {
            return isset($this->parameters[$key]);
        }
    }

}

