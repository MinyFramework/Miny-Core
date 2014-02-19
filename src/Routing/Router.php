<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Routing;

class Router
{
    /**
     * @var RouteMatcher
     */
    private $matcher;

    /**
     * @var RouteGenerator
     */
    private $generator;

    /**
     * @var RouteCollection
     */
    private $collection;
    private $routePrefix;
    private $routeSuffix;
    private $defaultParameters;
    private $shortUrlsEnabled;

    /**
     * @var Resources[]
     */
    private $resources = array();
    private $resourcesBuilt = false;

    /**
     *
     * @param string $prefix
     * @param string $suffix
     * @param array  $parameters
     * @param bool   $short_urls
     */
    public function __construct(
        $prefix = null,
        $suffix = null,
        array $parameters = array(),
        $short_urls = true
    ) {
        $this->collection = new RouteCollection();
        $this->matcher           = new RouteMatcher($this->collection);
        $this->generator         = new RouteGenerator($this->collection, $short_urls);
        $this->routePrefix       = $prefix;
        $this->routeSuffix       = $suffix;
        $this->defaultParameters = $parameters;
        $this->shortUrlsEnabled  = $short_urls;
    }

    public function shortUrls()
    {
        return $this->shortUrlsEnabled;
    }

    /**
     *
     * @param array   $parameters
     * @param boolean $prefix
     * @param boolean $suffix
     *
     * @return Route
     */
    public function root(array $parameters = array(), $prefix = true, $suffix = false)
    {
        $route = new Route('', 'GET', $parameters);

        return $this->route($route, 'root', $prefix, $suffix);
    }

    /**
     * @param Route   $route
     * @param mixed   $name
     * @param boolean $prefix
     * @param boolean $suffix
     *
     * @return Route
     */
    public function route(Route $route, $name, $prefix = true, $suffix = true)
    {
        $path = $route->getPath();
        if ($this->routePrefix !== null && $prefix) {
            $path = $this->routePrefix . $path;
            $route->setPath($path);
        }
        if ($this->routeSuffix !== null && $suffix) {
            $path .= $this->routeSuffix;
            $route->setPath($path);
        }
        if (!empty($this->defaultParameters)) {
            $parameters = $route->getParameters();
            $route->addParameters($this->defaultParameters);
            $route->addParameters($parameters);
        }
        $this->collection->addRoute($route, $name);

        return $route;
    }

    /**
     * @param string $name
     * @param array  $parameters
     *
     * @return Resources
     */
    public function resources($name, array $parameters = array())
    {
        $parameters        = $parameters + $this->defaultParameters;
        $resource          = new Resources($name, $parameters);
        $this->resources[] = $resource;

        return $resource;
    }

    /**
     * @param string $name
     * @param array  $parameters
     *
     * @return Resource
     */
    public function resource($name, array $parameters = array())
    {
        $parameters        = $parameters + $this->defaultParameters;
        $resource          = new Resource($name, $parameters);
        $this->resources[] = $resource;

        return $resource;
    }

    private function buildResources()
    {
        if ($this->resourcesBuilt) {
            return;
        }
        $this->resourcesBuilt = true;
        foreach ($this->resources as $resource) {
            foreach ($resource as $name => $route) {
                $this->route($route, $name);
            }
        }
    }

    /**
     * @param string $path
     * @param string $method
     *
     * @return Match
     */
    public function match($path, $method = null)
    {
        $this->buildResources();

        return $this->matcher->match($path, $method);
    }

    /**
     * @param string $route_name
     * @param array  $parameters
     *
     * @return string
     */
    public function generate($route_name, array $parameters = array())
    {
        $this->buildResources();

        return $this->generator->generate($route_name, $parameters);
    }

    /**
     * @return RouteCollection
     */
    public function getRouteCollection()
    {
        return $this->collection;
    }
}
