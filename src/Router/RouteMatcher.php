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
    private $routes;

    /**
     * @param Router $routes
     */
    public function __construct(Router $routes)
    {
        $this->routes = $routes;
    }

    /**
     * @param string $path
     * @param int    $method
     *
     * @return Match|boolean
     */
    public function match($path, $method = Route::METHOD_ALL)
    {
        if ($this->routes->hasStatic($path)) {
            $route = $this->routes->getStaticByURI($path);
            if ($route->isMethod($method)) {
                return new Match($route);
            }
        }

        $variableRoutes = $this->createChunks($method);

        return $this->matchChunkedVariableRoutes($path, $variableRoutes);
    }

    /**
     * @param $method
     *
     * @return array
     */
    private function createChunks($method)
    {
        $variableRoutes = array(array());
        $group          = 0;
        foreach ($this->routes->getAll() as $route) {
            /** @var $route Route */
            if ($route->isStatic() || !$route->isMethod($method)) {
                continue;
            }

            $variableRoutes[$group][] = $route;
            if (count($variableRoutes[$group]) === self::CHUNK_SIZE) {
                ++$group;
            }
        }

        return $variableRoutes;
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
        if (empty($variableRoutes)) {
            return false;
        }
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
