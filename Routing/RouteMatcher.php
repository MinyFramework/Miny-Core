<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Routing;

class RouteMatcher
{
    private $routes;

    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    public function match($path, $method = NULL)
    {
        foreach ($this->routes as $route) {
            $route_method = $route->getMethod();
            if ($method !== NULL && $route_method !== NULL && $method !== $route_method) {
                continue;
            }

            if ($route->isStatic()) {
                if ($path != $route->getPath()) {
                    continue;
                }
                return new Match($route);
            }

            $matched = array();
            if (preg_match('#^' . $route->getRegex() . '$#Du', $path, $matched)) {
                $matched_params = array();
                $parameter_count = $route->getParameterCount();
                for ($i = 1; $i < $parameter_count; ++$i) {
                    $matched_params[$route->getParameterName($i - 1)] = $matched[$i];
                }
                return new Match($route, $matched_params);
            }
        }
        return false;
    }

}