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

class RouteCollection implements IteratorAggregate
{
    private $routes = array();

    public function merge(RouteCollection $collection)
    {
        $this->routes = array_merge($this->routes, $collection->routes);
    }

    public function addRoute(Route $route, $name = NULL)
    {
        if (is_null($name)) {
            $this->routes[] = $route;
        } else {
            $this->routes[$name] = $route;
        }
    }

    public function addRoutes(array $routes)
    {
        foreach ($routes as $name => $route) {
            $this->addRoute($name, $route);
        }
    }

    public function getRoute($name)
    {
        if (!isset($this->routes[$name])) {
            throw new OutOfBoundsException('Route not set: ' . $name);
        }
        return $this->routes[$name];
    }

    public function getIterator()
    {
        return new ArrayIterator($this->routes);
    }

}