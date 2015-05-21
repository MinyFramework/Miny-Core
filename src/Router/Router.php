<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Router;

class Router
{
    /**
     * @var Route[]
     */
    private $routes = [];
    private $staticRoutes = [];
    private $globalValues = [];

    /**
     * @var AbstractRouteParser
     */
    private $parser;
    private $prefix = '';
    private $postfix = '';

    /**
     * @var \Miny\Router\Resource[]
     */
    private $resources = [];

    public function __construct(AbstractRouteParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @param string $postfix
     */
    public function setPostfix($postfix)
    {
        $this->postfix = $postfix;
    }

    /**
     * @param array $values
     */
    public function addGlobalValues(array $values)
    {
        $this->globalValues = $values + $this->globalValues;
    }

    /**
     * @param      $uri
     * @param int  $method
     * @param      $name
     * @param bool $prefix
     *
     * @throws \InvalidArgumentException
     * @return Route
     */
    public function add($uri, $method = Route::METHOD_ALL, $name = null, $prefix = false)
    {
        if ($prefix) {
            $uri = $this->prefix . $uri . $this->postfix;
        }

        $route = $this->parser->parse($uri, $method);
        if ($name === null || is_int($name)) {
            $name = count($this->routes);
        } elseif (!is_string($name)) {
            throw new \InvalidArgumentException('$name must be a string, integer or null.');
        }
        $this->routes[$name] = $route;
        if ($route->isStatic()) {
            if (!isset($this->staticRoutes[$uri])) {
                $this->staticRoutes[$uri] = [
                    $method => $route
                ];
            } else {
                $this->staticRoutes[$uri][$method] = $route;
            }
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
        return $this->add($uri, Route::METHOD_GET, $name, true);
    }

    public function post($uri, $name = null)
    {
        return $this->add($uri, Route::METHOD_POST, $name, true);
    }

    public function put($uri, $name = null)
    {
        return $this->add($uri, Route::METHOD_PUT, $name, true);
    }

    public function delete($uri, $name = null)
    {
        return $this->add($uri, Route::METHOD_DELETE, $name, true);
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

    /**
     * @param $name
     *
     * @return Route
     * @throws \OutOfBoundsException
     */
    public function getRoute($name)
    {
        if (!isset($this->routes[$name])) {
            throw new \OutOfBoundsException("Route {$name} is not found.");
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
     * @param $uri
     * @param $method
     *
     * @return bool
     */
    public function hasStatic($uri, $method = Route::METHOD_GET)
    {
        return isset($this->staticRoutes[$uri][$method]) || isset($this->staticRoutes[$uri][Route::METHOD_ALL]);
    }

    /**
     * @param     $uri
     * @param int $method
     * @return Route
     */
    public function getStaticByURI($uri, $method = Route::METHOD_GET)
    {
        if (!isset($this->staticRoutes[$uri][$method])) {
            if (isset($this->staticRoutes[$uri][Route::METHOD_ALL])) {
                return $this->staticRoutes[$uri][Route::METHOD_ALL];
            }
            throw new \OutOfBoundsException("Static uri {$uri} with method {$method} is not found.");
        }

        return $this->staticRoutes[$uri][$method];
    }

    public function resource($singularName, $pluralName = null)
    {
        $resource          = new Resource($singularName, $pluralName);
        $this->resources[] = $resource;

        return $resource;
    }

    public function registerResources()
    {
        foreach ($this->resources as $resource) {
            $resource->register($this);
        }
    }
}
