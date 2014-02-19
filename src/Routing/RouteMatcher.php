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
    const CHUNK_SIZE = 10;
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
        $variableRoutes = array(array());
        $group          = 0;
        foreach ($this->routes as $route) {
            /** @var $route Route */
            if (!$route->isMethod($method)) {
                continue;
            }

            if ($route->isStatic()) {
                if ($path !== $route->getPath()) {
                    continue;
                }

                return new Match($route);
            }
            $variableRoutes[$group][] = $route;
            if (count($variableRoutes[$group]) === self::CHUNK_SIZE) {
                ++$group;
            }
        }

        return $this->matchChunkedVariableRoutes($path, $variableRoutes);
    }

    /**
     * @param $path
     * @param $variableRoutes
     *
     * @return bool|Match
     */
    private function matchChunkedVariableRoutes($path, $variableRoutes)
    {
        foreach ($variableRoutes as $routes) {
            if (empty($variableRoutes)) {
                continue;
            }
            $return = $this->matchVariableRoutes($path, $routes);
            if ($return) {
                return $return;
            }
        }

        return false;
    }

    /**
     * @param $path
     * @param $variableRoutes
     *
     * @return bool|Match
     */
    private function matchVariableRoutes($path, $variableRoutes)
    {
        $numGroups = 0;
        $indexes   = array();
        $patterns  = array();
        foreach ($variableRoutes as $i => $route) {
            /** @var $route Route */
            $numVariables = $route->getParameterCount();
            $numGroups    = max($numGroups, $numVariables);

            $patterns[] = $route->getRegex() . str_repeat('()', $numGroups - $numVariables);

            $indexes[$numGroups++] = $i;
        }
        $pattern = '#^(?|' . implode('|', $patterns) . ')$#';
        if (!preg_match($pattern, $path, $matched)) {
            return false;
        }

        $route = $variableRoutes[$indexes[count($matched) - 1]];
        return $this->createMatch($route, $matched);
    }

    /**
     * @param Route $route
     * @param array $matched
     *
     * @return Match
     */
    private function createMatch(Route $route, $matched)
    {
        $matchedParams = array();
        foreach ($route->getParameterNames() as $i => $name) {
            $matchedParams[$name] = $matched[$i + 1];
        }

        return new Match($route, $matchedParams);
    }
}
