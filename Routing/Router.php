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
    private $matcher;
    private $generator;
    private $route_prefix;
    private $route_suffix;
    private $default_parameters;
    private $resources = array();
    private $resources_built = false;

    public function __construct($prefix = NULL, $suffix = NULL, array $parameters = array())
    {
        $this->matcher = new RouteMatcher($this);
        $this->generator = new RouteGenerator($this);
        $this->route_prefix = $prefix;
        $this->route_suffix = $suffix;
        $this->default_parameters = $parameters;
    }

    public function root(array $parameters = array(), $prefix = true, $suffix = false)
    {
        $route = new Route('', 'GET', $parameters);
        $this->route($route, 'root', $prefix, $suffix);
    }

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
            $route->addParameters($this->default_parameters);
        }
        $this->addRoute($route, $name);
        return $route;
    }

    public function resource($name, array $parameters = array(), $singular = false)
    {
        $parameters = $parameters + $this->default_parameters;
        if ($singular) {
            $resource = new Resource($name, $parameters);
        } else {
            $resource = new Resources($name, $parameters);
        }
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
                    $name = NULL;
                }
                $this->route($route, $name);
            }
        }
    }

    public function match($path, $method = NULL)
    {
        $this->buildResources();
        return $this->matcher->match($path, $method);
    }

    public function generate($route_name, array $parameters = array())
    {
        $this->buildResources();
        return $this->generator->generate($route_name, $parameters);
    }

}