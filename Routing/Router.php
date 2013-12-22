<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Routing;

class Router extends RouteCollection
{
    /**
     * @var RouteMatcher
     */
    private $matcher;

    /**
     * @var RouteGenerator
     */
    private $generator;
    private $route_prefix;
    private $route_suffix;
    private $default_parameters;
    private $short_urls;

    /**
     * @var Resources[]
     */
    private $resources       = array();
    private $resources_built = false;

    /**
     *
     * @param string $prefix
     * @param string $suffix
     * @param array $parameters
     */
    public function __construct($prefix = null, $suffix = null, array $parameters = array(), $short_urls = true)
    {
        $this->matcher            = new RouteMatcher($this);
        $this->generator          = new RouteGenerator($this, $short_urls);
        $this->route_prefix       = $prefix;
        $this->route_suffix       = $suffix;
        $this->default_parameters = $parameters;
        $this->short_urls         = $short_urls;
    }

    public function shortUrls()
    {
        return $this->short_urls;
    }

    /**
     *
     * @param array $parameters
     * @param boolean $prefix
     * @param boolean $suffix
     * @return Route
     */
    public function root(array $parameters = array(), $prefix = true, $suffix = false)
    {
        $route = new Route('', 'GET', $parameters);
        return $this->route($route, 'root', $prefix, $suffix);
    }

    /**
     * @param Route $route
     * @param string $name
     * @param boolean $prefix
     * @param boolean $suffix
     * @return Route
     */
    public function route(Route $route, $name, $prefix = true, $suffix = true)
    {
        if (!is_null($this->route_prefix) || !is_null($this->route_suffix)) {
            $path = $route->getPath();
            if ($prefix) {
                $path = $this->route_prefix . $path;
            }
            if ($suffix) {
                $path .= $this->route_suffix;
            }
            $route->setPath($path);
        }
        if (!empty($this->default_parameters)) {
            $parameters = $route->getParameters();
            $route->addParameters($this->default_parameters);
            $route->addParameters($parameters);
        }
        $this->addRoute($route, $name);
        return $route;
    }

    /**
     * @param type $name
     * @param array $parameters
     * @param type $singular
     * @return Resources
     */
    public function resources($name, array $parameters = array())
    {
        $parameters        = $parameters + $this->default_parameters;
        $resource          = new Resources($name, $parameters);
        $this->resources[] = $resource;
        return $resource;
    }

    /**
     * @param type $name
     * @param array $parameters
     * @return Resource
     */
    public function resource($name, array $parameters = array())
    {
        $parameters        = $parameters + $this->default_parameters;
        $resource          = new Resource($name, $parameters);
        $this->resources[] = $resource;
        return $resource;
    }

    private function buildResources()
    {
        if ($this->resources_built) {
            return;
        }
        $this->resources_built = true;
        foreach ($this->resources as $resource) {
            foreach ($resource as $name => $route) {
                if (is_numeric($name)) {
                    $name = null;
                }
                $this->route($route, $name);
            }
        }
    }

    /**
     * @param string $path
     * @param string $method
     * @return Match
     */
    public function match($path, $method = null)
    {
        $this->buildResources();
        return $this->matcher->match($path, $method);
    }

    /**
     * @param string $route_name
     * @param array $parameters
     * @return string
     */
    public function generate($route_name, array $parameters = array())
    {
        $this->buildResources();
        return $this->generator->generate($route_name, $parameters);
    }
}
