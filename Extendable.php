<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny;

use BadMethodCallException;
use Closure;
use InvalidArgumentException;

class Extendable
{
    private $plugins = array();

    public function addMethod($method, $callback)
    {
        if (!is_callable($callback) && !$callback instanceof Closure) {
            throw new InvalidArgumentException('Callback must be callable');
        }
        $this->plugins[$method] = $callback;
    }

    public function addMethods($object, array $method_aliasses = array())
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException('First argument must be an object');
        }
        foreach ($method_aliasses as $alias => $method) {
            if (!method_exists($object, $method)) {
                throw new InvalidArgumentException('Method not found: ' . $method);
            }
            if (is_numeric($alias)) {
                $alias = $method;
            }
            $this->plugins[$alias] = array($object, $method);
        }
    }

    public function __call($method, $args)
    {
        if (!isset($this->plugins[$method])) {
            throw new BadMethodCallException('Method not found: ' . $method);
        }
        return call_user_func_array($this->plugins[$method], $args);
    }

}