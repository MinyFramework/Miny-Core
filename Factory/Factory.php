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
 * Factory class
 * Responsible for storing ObjectDescriptors and their dependencies, parameters.
 * Factory instantiates stored objects on demand and injects them with specified
 * dependencies.
 * For usage, see @link http://prominence.bugadani.hu/packages/factory
 *
 * @author  Dániel Buga
 */
class Factory
{
    /**
     * A name => object array of stored objects
     * @var array
     */
    protected $objects = array();

    /**
     * A name => object array of stored ObjectDescriptors
     * @var array
     */
    protected $descriptors = array();

    /**
     * A name => value array of stored parameters
     * @var array
     */
    protected $parameters = array();

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
     * @param string $classname
     * @param boolean $singleton
     * @return ObjectDescriptor
     */
    public function add($alias, $classname, $singleton = true)
    {
        $object = new ObjectDescriptor($classname, $singleton);
        return $this->register($alias, $object);
    }

    /**
     * Registers an ObjectDescriptor with the given alias.
     * Unsets any existing instances for the given alias.
     *
     * @param string $alias
     * @param ObjectDescriptor $object
     * @return ObjectDescriptor
     */
    public function register($alias, ObjectDescriptor $object)
    {
        $this->descriptors[$alias] = $object;
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
        $this->objects[$alias] = $object;
    }

    public function __set($alias, $object)
    {
        if ($object instanceof ObjectDescriptor) {
            $this->register($alias, $object);
        } else {
            $this->setInstance($alias, $object);
        }
    }

    /**
     * Gets the ObjectDescriptor stored for $alias.
     *
     * @param string $alias
     * @return ObjectDescriptor
     * @throws OutOfBoundsException
     */
    public function getDescriptor($alias)
    {
        if (!isset($this->descriptors[$alias])) {
            $message = 'Object descriptor not found: ' . $alias;
            throw new \OutOfBoundsException($message);
        }
        return $this->descriptors[$alias];
    }

    /**
     * Creates an object using information stored in ObjectDescriptor class.
     * If an object for the given $alias already exists,
     * returns with the stored object.
     *
     * @param string $alias
     * @return object
     */
    public function get($alias)
    {
        if (isset($this->objects[$alias])) {
            return $this->objects[$alias];
        }

        $descriptor = $this->getDescriptor($alias);
        $obj = $this->instantiate($descriptor);

        if ($descriptor->isSingleton()) {
            $this->setInstance($alias, $obj);
        }

        $this->injectDependencies($obj, $descriptor);
        return $obj;
    }

    /**
     * Shorthand function for {get}
     */
    public function __get($alias)
    {
        return $this->get($alias);
    }

    /**
     * Injects the object with it's dependencies.
     *
     * @param object $object
     * @param ObjectDesciptor $descriptor
     */
    private function injectDependencies($object, ObjectDescriptor $descriptor)
    {
        if ($descriptor->hasParent()) {
            $parent = $this->getDescriptor($descriptor->getParent());
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

    /**
     * Instantiates the given ObjectDescriptor and injects it with the
     * constructor's parameters.
     *
     * @param ObjectDescriptor $descriptor
     * @return object
     * @throws InvalidArgumentException
     */
    private function instantiate(ObjectDescriptor $descriptor)
    {
        $class = $descriptor->getClassName();
        if ($class[0] == '@') {
            $class = $this->getParameter(substr($class, 1));
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
                $ref = new \ReflectionClass($class);
                $args = array_map(array($this, 'getValue'), $args);
                $obj = $ref->newInstanceArgs($args);
        }
        return $obj;
    }

    /**
     * Resolves parameter references.
     * If the parameter is a string, the first character specifies the value
     * type:
     *  - @: A parameter, @see Factory::getParameter()
     *  - &: An instance or the return value of a method call.
     *       Syntax to define a method call:
     *       object::method[::parameter1::parameter2...]
     *       At this moment, only string or integer parameters are supported.
     *  - *: A callback function. This can be a function or a method of an
     *       object.
     *       To define a function, give simply the function name.
     *       To define an object, use the object::method syntax.
     * If the $var parameter is not a string, or is only a character it is
     * returned as is.
     * If you wish to pass a string beginning with one of these characters, you
     * need to escape the first character. To do that, simply prefix it with a
     * backslash (\) character. The backslash character must also be escaped the
     * same way if you want to pass a string starting with a backlash followed
     * by a parameter specifier. In practice it means, that in order to pass a
     * string starting with *, &, @, \*, \&, \@ character sequence, you must
     * prefix those with a backslash.
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
        //direct injection for non-string values
        if (!is_string($var) || strlen($var) === 1) {
            return $var;
        }
        //see if $var is a reference to something
        $str = substr($var, 1);
        switch ($var[0]) {
            case '@'://param
                $var = $this->getParameter($str);
                break;
            case '&':
                /*
                 * object or method call. Basically this is
                 * $factory->create('object')->method(parameters);
                 */
                $var = $this->getObjectParameter($str);
                break;
            case '*'://callback
                if (strpos($var, '::') !== false) {
                    list($obj_name, $method) = explode('::', $str);
                    $var = array($this->get($obj_name), $method);
                }
                break;
            case '\\':
                /*
                 * if parameter is string beginning
                 * with *, &, @ and \, those must be escaped
                 */
                $escaped = array('\\', '*', '&', '@');
                if (in_array($var[1], $escaped)) {
                    $var = $str;
                }
                break;
        }
        return $var;
    }

    /**
     * Processes a parameter string, which specifies an object or a method call.
     *
     * @see Factory::getValue()
     * @param string $str
     * @return mixed
     */
    private function getObjectParameter($str)
    {
        if (($pos = strpos($str, '::')) !== false) {
            $arr = explode('::', $str);
            $obj_name = array_shift($arr);
            $method = array_shift($arr);
            $object = $this->get($obj_name);
            return call_user_func_array(array($object, $method), $arr);
        } else {
            return $this->get($str);
        }
    }

    /**
     * Processes a parameter string, which specifies a stored parameter.
     * The method is capable of indexing arrays. To get a value from an array,
     * separate the array name and index with a colon (:)
     *
     * @example
     * Assume, that parameter foo is an array. Foo is defined as
     * $foo = array(
     *     'bar' => 'baz'
     * );
     *
     * In order to receive the value contained under bar, use
     * $factory->getParameter('foo:bar');
     *
     * @param string $key The parameter to get.
     * @return mixed The parameter value
     * @throws LogicException
     */
    public function getParameter($key)
    {
        if (strpos($key, ':') !== false) {
            $parts = explode(':', $key);
            $arr = $this->parameters;
            foreach ($parts as $k) {
                if (!array_key_exists($k, $arr)) {
                    $message = 'Parameter not set: ' . $key;
                    throw new \OutOfBoundsException($message);
                }
                $arr = $arr[$k];
            }
            return $arr;
        } elseif (!isset($this->parameters[$key])) {
            $message = 'Parameter not set: ' . $key;
            throw new \OutOfBoundsException($message);
        }
        return $this->parameters[$key];
    }

    /**
     * Stores a parameter for $key serving as key.
     *
     * @param string $key
     * @param mixed $value
     */
    public function addParameter($key, $value)
    {
        if (strpos($key, ':') !== false) {
            $parts = explode(':', $key);
            $arr = & $this->parameters;
            foreach ($parts as $k) {
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
     * Stores an array of parameters.
     *
     * @param array $parameters A name => value array of parameters to store.
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters + $this->parameters;
    }

    /**
     * Removes the parameter specified with $key.
     *
     * @param string $key
     */
    public function removeParameter($key)
    {
        if (strpos($key, ':') !== false) {
            $parts = explode(':', $key);
            $arr = & $this->parameters;
            foreach ($parts as $k) {
                if (!array_key_exists($k, $arr)) {
                    return;
                }
                if ($k !== end($parts)) {
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
    public function hasParameter($key)
    {
        if (strpos($key, ':') !== false) {
            $parts = explode(':', $key);
            $arr = $this->parameters;
            foreach ($parts as $k) {
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
     * Retrieves all stored ObjectDescriptors.
     *
     * @return array
     */
    public function getDescriptors()
    {
        return $this->descriptors;
    }

}