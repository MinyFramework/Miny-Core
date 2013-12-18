<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Routing;

use ArrayIterator;
use IteratorAggregate;
use OutOfBoundsException;
use UnexpectedValueException;

class RouteCollection implements IteratorAggregate
{
    /**
     * @var Route[]
     */
    private $routes = array();

    /**
     * @param RouteCollection $collection
     */
    public function merge(RouteCollection $collection)
    {
        $this->routes = array_merge($this->routes, $collection->routes);
    }

    /**
     * @param Route $route
     * @param string $name
     * @throws UnexpectedValueException
     */
    public function addRoute(Route $route, $name = NULL)
    {
        if (is_null($name)) {
            $this->routes[] = $route;
        } else {
            if (!is_string($name)) {
                throw new UnexpectedValueException('Parameter "name" must be a string or NULL.');
            }
            $this->routes[$name] = $route;
        }
    }

    /**
     * @param string $name
     * @return Route
     * @throws OutOfBoundsException
     */
    public function getRoute($name)
    {
        if (!is_string($name)) {
            throw new UnexpectedValueException('Parameter "name" must be a string.');
        }
        if (!isset($this->routes[$name])) {
            throw new OutOfBoundsException('Route not set: ' . $name);
        }
        return $this->routes[$name];
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->routes);
    }
}
