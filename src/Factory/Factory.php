<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Factory;

use ArrayAccess;
use Closure;
use InvalidArgumentException;
use Miny\Utils\Utils;
use OutOfBoundsException;

/**
 * Factory class
 *
 * Responsible for storing Blueprints and their dependencies, parameters.
 * Factory instantiates stored objects on demand and injects them with specified
 * dependencies.
 */
class Factory implements ArrayAccess
{
    /**
     * @var object[]
     */
    protected $objects = array();

    /**
     * @var (Blueprint|Closure)[]
     */
    protected $blueprints = array();

    /**
     * @var ParameterContainer
     */
    protected $parameters;

    /**
     * @var string[]
     */
    protected $aliases = array();

    /**
     * @param array|ParameterContainer|null $params Initial list of parameters to be stored.
     */
    public function __construct($params = null)
    {
        if (!$params instanceof ParameterContainer) {
            $params = new ParameterContainer($params ? : array());
        }
        $this->parameters = $params;
        $this->setObjectInstance('factory', $this);
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
        $this->aliases[$alias] = $target;
    }

    /**
     * @param string $alias
     *
     * @return string
     */
    public function getAlias($alias)
    {
        if (isset($this->aliases[$alias])) {
            $alias = $this->aliases[$alias];
        }
        return $alias;
    }

    /**
     * Creates a Blueprint for $classname and registers it with $alias.
     *
     * @param string  $alias
     * @param string  $classname
     * @param boolean $singleton
     *
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
     * @param string    $alias
     * @param Blueprint $object
     *
     * @return Blueprint
     */
    public function register($alias, Blueprint $object)
    {
        $this->blueprints[$alias] = $object;
        unset($this->objects[$alias]);
        return $object;
    }

    private function setObjectInstance($alias, $object)
    {
        $this->objects[$alias] = $object;
        return $object;
    }

    /**
     * Adds an object instance.
     *
     * @see Factory::__get()
     *
     * @param string $alias
     * @param object $object
     *
     * @throws InvalidArgumentException
     * @return object $object
     */
    public function setInstance($alias, $object)
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException('Factory::setInstance needs an object for alias ' . $alias);
        }
        return $this->setObjectInstance($alias, $object);
    }

    /**
     * @param string $alias
     * @param object $object
     *
     * @throws InvalidArgumentException
     */
    public function __set($alias, $object)
    {
        if (is_string($object) || $object instanceof Closure) {
            $this->add($alias, $object);
        } elseif ($object instanceof Blueprint) {
            $this->register($alias, $object);
        } elseif (is_object($object)) {
            $this->setObjectInstance($alias, $object);
        } else {
            throw new InvalidArgumentException('Factory::__set expects a string or an object.');
        }
    }

    /**
     * Checks whether $alias is registered.
     *
     * @param string $alias
     *
     * @return bool
     */
    public function has($alias)
    {
        $alias = $this->getAlias($alias);
        return isset($this->blueprints[$alias]) || isset($this->objects[$alias]);
    }

    /**
     * Gets the Blueprint stored for $alias.
     *
     * @param string $alias
     *
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
     * Replaces an instance with another if instantiated.
     *
     * @param string      $alias
     * @param object|null $object
     *
     * @return null|object The old object instance.
     *
     * @throws InvalidArgumentException
     */
    public function replace($alias, $object = null)
    {
        $alias = $this->getAlias($alias);
        if (isset($this->objects[$alias])) {
            $old = $this->objects[$alias];
            unset($this->objects[$alias]);
        } else {
            $old = null;
        }

        if ($object === null) {
            $this->get($alias);
        } elseif (is_object($object)) {
            $this->__set($alias, $object);
        } else {
            throw new InvalidArgumentException('Can only insert objects.');
        }

        return $old;
    }

    /**
     * Returns with the object stored under $alias.
     * The method creates the object instance if needed.
     *
     * @param string $alias
     *
     * @return object
     */
    public function get($alias)
    {
        $alias = $this->getAlias($alias);
        if (isset($this->objects[$alias])) {
            return $this->objects[$alias];
        }

        $descriptor = $this->getBlueprint($alias);

        $obj = $this->instantiate($descriptor);
        if ($descriptor->isSingleton()) {
            $this->setObjectInstance($alias, $obj);
        }

        return $this->injectDependencies($obj, $descriptor);
    }

    /**
     * @param object    $object
     * @param Blueprint $blueprint
     *
     * @return object
     */
    private function injectDependencies($object, Blueprint $blueprint)
    {
        if ($blueprint->hasParent()) {
            $parent = $this->getBlueprint($blueprint->getParent());
            $this->injectDependencies($object, $parent);
        }
        foreach ($blueprint->getProperties() as $name => $value) {
            $object->$name = $this->resolveReferences($value);
        }
        foreach ($blueprint->getMethodCalls() as $method) {
            list($name, $args) = $method;
            $arguments = $this->resolveReferences($args);
            call_user_func_array(array($object, $name), $arguments);
        }
        return $object;
    }

    /**
     * @param Blueprint $blueprint
     *
     * @return object
     *
     * @throws InvalidArgumentException
     */
    private function instantiate(Blueprint $blueprint)
    {
        $class = $blueprint->getClassName();

        $args = $blueprint->getArguments();
        while (empty($args) && $blueprint->hasParent()) {
            $blueprint = $this->getBlueprint($blueprint->getParent());
            $args      = $blueprint->getArguments();
        }
        $arguments = $this->resolveReferences($args);

        if ($class instanceof Closure) {
            return call_user_func_array($class, $arguments);
        }

        if ($class[0] == '@') {
            $class = $this->parameters[substr($class, 1)];
        }

        return Utils::instantiate($class, $arguments);
    }

    /**
     * Resolves parameter references recursively.
     *
     * @param mixed $var
     *
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
        if (strpos($var, '{@') !== false) {
            $var = $this->parameters->resolveLinks($var);
        }
        //see if $var is a reference to something
        $str = substr($var, 1);
        switch ($var[0]) {
            case '@':
                //parameter
                $var = $this->offsetGet($str);
                break;

            case '&':
                //object or method call. Basically this is
                //$factory->create('object')->method(parameters);
                $var = $this->getObjectParameter($str);
                break;

            case '*':
                //callback
                if (strpos($var, '::') !== false) {
                    list($obj_name, $method) = explode('::', $str, 2);
                    $var = array($this->get($obj_name), $method);
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
     *
     * @throws InvalidArgumentException
     * @return mixed
     */
    private function getObjectParameter($str)
    {
        if (strpos($str, '::') !== false) {
            //method parameters are separated by ::
            //TODO: use different separator for method names and arguments, support for nested method calls
            $arr      = explode('::', $str);
            $obj_name = array_shift($arr);
            $method   = array_shift($arr);
            $object   = $this->get($obj_name);

            $callback = array($object, $method);
            if (!is_callable($callback)) {
                $method = sprintf('Class "%s" does not have a method "%s"', $obj_name, $method);
                throw new InvalidArgumentException($method);
            }
            $retval = call_user_func_array($callback, $this->parameters->resolveLinks($arr));
        } elseif (strpos($str, '->') !== false) {
            list($obj_name, $property) = explode('->', $str, 2);
            $retval = $this->get($obj_name)->$property;
        } else {
            $retval = $this->get($str);
        }
        return $this->resolveReferences($retval);
    }

    public function __isset($alias)
    {
        return $this->has($alias);
    }

    public function __get($alias)
    {
        return $this->get($alias);
    }

    /* ArrayAccess interface */

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
