<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Routing;

use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;
use OutOfBoundsException;

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
        $this->routes = array_merge($this->routes, $collection->getRoutes());
    }

    /**
     * @return Route[]
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @param Route $route
     * @param mixed $name
     * @throws InvalidArgumentException
     */
    public function addRoute(Route $route, $name = null)
    {
        if ($name === null || is_int($name)) {
            $this->routes[] = $route;
        } elseif (is_string($name)) {
            $this->routes[$name] = $route;
        } else {
            throw new InvalidArgumentException('Parameter "name" must be a string, integer or NULL.');
        }
    }

    /**
     * @param string $name
     *
     * @return Route
     *
     * @throws InvalidArgumentException
     * @throws OutOfBoundsException
     */
    public function getRoute($name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Parameter "name" must be a string.');
        }
        if (!isset($this->routes[$name])) {
            throw new OutOfBoundsException('Route not found: ' . $name);
        }
        return $this->routes[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasRoute($name)
    {
        return isset($this->routes[$name]);
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->routes);
    }
}
