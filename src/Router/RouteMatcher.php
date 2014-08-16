<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
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
        $routes = [];
        foreach ($this->router->getAll() as $route) {
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
            $routes = [];
        }

        return $this->matchVariableRouteChunk($path, $routes);
    }

    /**
     * @param string  $path
     * @param Route[] $variableRoutes
     *
     * @return bool|Match
     */
    private function matchVariableRouteChunk($path, $variableRoutes)
    {
        $numGroups = 0;
        $indexes   = [];
        $pattern   = '';
        foreach ($variableRoutes as $route) {
            $numVariables = $route->getParameterCount();
            $pattern .= '|' . $route->getRegexp();
            if ($numVariables < $numGroups && !isset($indexes[$numVariables])) {
                $indexes[$numVariables] = $route;
            } else {
                $numGroups = max($numGroups, $numVariables);
                $pattern .= str_repeat('()', $numGroups - $numVariables);
                $indexes[$numGroups++] = $route;
            }
        }
        if (!preg_match('#^(?' . $pattern . ')$#', $path, $matched)) {
            return false;
        }

        $route = $indexes[count($matched) - 1];

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
        $matchedParams = [];
        foreach ($route->getParameterNames() as $i => $name) {
            $matchedParams[$name] = $matched[$i + 1];
        }

        return new Match($route, $matchedParams);
    }
}
