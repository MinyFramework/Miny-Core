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
    /**
     * @var RouteCollection
     */
    private $routes;

    /**
     * @param RouteCollection $routes
     */
    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    /**
     * @param string $path
     * @param string $method
     *
     * @return Match|boolean
     */
    public function match($path, $method = null)
    {
        foreach ($this->routes as $route) {
            /** @var $route Route */
            if (!$this->methodMatches($method, $route)) {
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

    /**
     * @param string $method
     * @param Route  $route
     *
     * @return bool
     */
    private function methodMatches($method, Route $route)
    {
        $route_method = $route->getMethod();
        if ($method === null) {
            return true;
        }
        if ($route_method === null) {
            return true;
        }

        return $method === $route_method;
    }
}
