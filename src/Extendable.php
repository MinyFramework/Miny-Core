<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny;

use BadMethodCallException;
use InvalidArgumentException;

class Extendable
{
    /**
     * @var callable[]
     */
    private $plugins = array();

    /**
     * @var callable[]
     */
    private $setters = array();

    /**
     * Dynamically add a method to the class.
     *
     * @param string   $method
     * @param callable $callback
     *
     * @throws InvalidArgumentException
     */
    public function addMethod($method, $callback)
    {
        if (!is_string($method)) {
            throw new InvalidArgumentException('Parameter "method" must be string');
        }
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Callback given for method ' . $method . ' is not callable');
        }
        $this->plugins[$method] = $callback;
    }

    /**
     * Link multiple methods from $object to the current class.
     * Methods can be renamed by specifying aliases as array keys in $method_aliases
     *
     * @param object $object
     * @param array  $method_aliases
     *
     * @throws InvalidArgumentException
     */
    public function addMethods($object, array $method_aliases = array())
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException('First argument must be an object');
        }
        foreach ($method_aliases as $alias => $method) {
            $callable = array($object, $method);
            if (!is_callable($callable)) {
                $message = sprintf('Method "%s" not found in class %s', $method, get_class($object));
                throw new InvalidArgumentException($message);
            }
            if (is_int($alias)) {
                $alias = $method;
            }
            $this->plugins[$alias] = $callable;
        }
    }

    /**
     * Define a setter for $property.
     * The setter will have the name set(property_name) unless specified in $setter.
     *
     * @param string $property
     * @param string $setter
     */
    public function addSetter($property, $setter = null)
    {
        if ($setter === null) {
            $setter = 'set' . ucfirst($property);
        }
        $this->setters[$setter] = $property;
    }

    /**
     * Register multiple property setters.
     *
     * @param array $setters
     */
    public function addSetters(array $setters)
    {
        foreach ($setters as $property => $setter) {
            if (is_numeric($property)) {
                $property = $setter;
                $setter   = 'set' . ucfirst($setter);
            }
            $this->setters[$setter] = $property;
        }
    }

    /**
     * @param string $method
     * @param array  $args
     *
     * @throws InvalidArgumentException
     * @throws BadMethodCallException
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (!is_string($method)) {
            throw new InvalidArgumentException('Parameter "method" must be string');
        }
        if (isset($this->setters[$method])) {
            $this->{$this->setters[$method]} = current($args);
        } else {
            if (!isset($this->plugins[$method])) {
                throw new BadMethodCallException('Method not found: ' . $method);
            }
            return call_user_func_array($this->plugins[$method], $args);
        }
    }
}
