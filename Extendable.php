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
    private $plugins = array();

    /**
     *
     * @param string $method
     * @param callable $callback
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
     *
     * @param object $object
     * @param array $method_aliasses
     * @throws InvalidArgumentException
     */
    public function addMethods($object, array $method_aliasses = array())
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException('First argument must be an object');
        }
        foreach ($method_aliasses as $alias => $method) {
            $callable = array($object, $method);
            if (!is_callable($callable)) {
                $message = sprintf('Method "%s" not found in class %s', $method, get_class($object));
                throw new InvalidArgumentException($message);
            }
            if (is_numeric($alias)) {
                $alias = $method;
            }
            $this->plugins[$alias] = $callable;
        }
    }

    /**
     *
     * @param string $method
     * @param array $args
     * @return type
     * @throws BadMethodCallException
     */
    public function __call($method, $args)
    {
        if (!is_string($method)) {
            throw new InvalidArgumentException('Parameter "method" must be string');
        }
        if (!isset($this->plugins[$method])) {
            throw new BadMethodCallException('Method not found: ' . $method);
        }
        return call_user_func_array($this->plugins[$method], $args);
    }

}
