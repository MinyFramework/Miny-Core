<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Router;

class RouteMatcher
{
    const CHUNK_SIZE = 10;

    /**
     * @var Router
     */
    private $router;

    /**
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param string $path
     * @param int    $method
     *
     * @return Match|boolean
     */
    public function match($path, $method = Route::METHOD_ALL)
    {
        if ($this->router->hasStatic($path)) {
            $route = $this->router->getStaticByURI($path);
            if ($route->isMethod($method)) {
                return new Match($route);
            }
        }

        return $this->matchVariableRoutes($path, $method);
    }

    private function matchVariableRoutes($path, $method)
    {
        $count  = 0;
        $routes = array();
        foreach ($this->router->getAll() as $route) {
            /** @var $route Route */
            if ($route->isStatic() || !$route->isMethod($method)) {
                continue;
            }

            $routes[] = $route;
            if (++$count < self::CHUNK_SIZE) {
                continue;
            }
            $return = $this->matchVariableRouteChunk($path, $routes);
            if ($return) {
                return $return;
            }
            $count  = 0;
            $routes = array();
        }

        return false;
    }

    /**
     * @param $path
     * @param $variableRoutes
     *
     * @return bool|Match
     */
    private function matchVariableRouteChunk($path, $variableRoutes)
    {
        $numGroups = 0;
        $indexes   = array();
        $pattern   = '#^(?';
        foreach ($variableRoutes as $i => $route) {
            /** @var $route Route */
            $numVariables = $route->getParameterCount();
            $numGroups    = max($numGroups, $numVariables);

            $pattern .= '|' . $route->getRegexp() . str_repeat('()', $numGroups - $numVariables);

            $indexes[$numGroups++] = $i;
        }
        $pattern .= ')$#';
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
