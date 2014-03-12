<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Router;

use InvalidArgumentException;
use OutOfBoundsException;

class Router
{
    /**
     * @var Route[]
     */
    private $routes;
    private $staticRoutes;
    private $globalValues;

    /**
     * @var RouteParser
     */
    private $parser;
    private $prefix;
    private $postfix;

    public function __construct(RouteParser $parser)
    {
        $this->parser       = $parser;
        $this->prefix       = '';
        $this->postfix      = '';
        $this->routes       = array();
        $this->staticRoutes = array();
        $this->globalValues = array();
    }

    /**
     * @param string $prefix
     *
     * @throws InvalidArgumentException
     */
    public function setPrefix($prefix)
    {
        if (!is_string($prefix)) {
            throw new InvalidArgumentException('$prefix must be a string');
        }
        $this->prefix = $prefix;
    }

    /**
     * @param string $postfix
     *
     * @throws InvalidArgumentException
     */
    public function setPostfix($postfix)
    {
        if (!is_string($postfix)) {
            throw new InvalidArgumentException('$postfix must be a string');
        }
        $this->postfix = $postfix;
    }

    public function addGlobalValues($values)
    {
        $this->globalValues = $values + $this->globalValues;
    }

    /**
     * @param $uri
     * @param $method
     * @param $name
     *
     * @throws InvalidArgumentException
     *
     * @return Route
     */
    public function add($uri, $method = Route::METHOD_ALL, $name = null)
    {
        $route = $this->parser->parse($uri, $method);
        if ($name === null || is_int($name)) {
            $name = count($this->routes);
        } elseif (!is_string($name)) {
            throw new InvalidArgumentException('$name must be a string, integer or null.');
        }
        $this->routes[$name] = $route;
        if ($route->isStatic()) {
            $this->staticRoutes[$uri] = $route;
        }
        $route->set($this->globalValues);

        return $route;
    }

    public function root()
    {
        return $this->add($this->prefix, Route::METHOD_GET, 'root');
    }

    public function get($uri, $name = null)
    {
        $uri = $this->prefix . $uri . $this->postfix;

        return $this->add($uri, Route::METHOD_GET, $name);
    }

    public function post($uri, $name = null)
    {
        $uri = $this->prefix . $uri . $this->postfix;

        return $this->add($uri, Route::METHOD_POST, $name);
    }

    public function put($uri, $name = null)
    {
        $uri = $this->prefix . $uri . $this->postfix;

        return $this->add($uri, Route::METHOD_PUT, $name);
    }

    public function delete($uri, $name = null)
    {
        $uri = $this->prefix . $uri . $this->postfix;

        return $this->add($uri, Route::METHOD_DELETE, $name);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->routes[$name]);
    }

    public function getRoute($name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('$name must be a string.');
        }
        if (!isset($this->routes[$name])) {
            throw new OutOfBoundsException(sprintf('Route %s is not found.', $name));
        }

        return $this->routes[$name];
    }

    /**
     * @return Route[]
     */
    public function getAll()
    {
        return $this->routes;
    }

    /**
     * Determines if a static route $path exists.
     *
     * @param $path
     *
     * @return bool
     */
    public function hasStatic($path)
    {
        return isset($this->staticRoutes[$path]);
    }

    /**
     * @param $uri
     *
     * @return Route
     * @throws OutOfBoundsException
     * @throws InvalidArgumentException
     */
    public function getStaticByURI($uri)
    {
        if (!is_string($uri)) {
            throw new InvalidArgumentException('$uri must be a string.');
        }
        if (!isset($this->staticRoutes[$uri])) {
            throw new OutOfBoundsException(sprintf('Static uri %s is not found.', $uri));
        }

        return $this->staticRoutes[$uri];
    }
}
