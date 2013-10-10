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

    /**
     *
     * @param \Miny\Routing\RouteCollection $routes
     */
    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    /**
     *
     * @param string $path
     * @param string $method
     * @return \Miny\Routing\Match|boolean
     */
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

            if (preg_match('#^' . $route->getRegex() . '$#Du', $path, $matched)) {
                $matched_params = array();
                foreach ($route->getParameterNames() as $i => $name) {
                    $matched_params[$name] = $matched[$i + 1];
                }
                return new Match($route, $matched_params);
            }
        }
        return false;
    }

}
