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
        if ($this->router->hasStatic($path, $method)) {
            return new Match($this->router->getStaticByURI($path, $method));
        }

        return $this->matchVariableRoutes($path, $method);
    }

    private function matchVariableRoutes($path, $method)
    {
        //Filter out routes which are static and/or do not handle the current method
        $filteredRoutes = array_filter(
            $this->router->getAll(),
            function (Route $route) use ($method) {
                return !$route->isStatic() && $route->isMethod($method);
            }
        );

        $chunks = array_chunk($filteredRoutes, self::CHUNK_SIZE);
        foreach ($chunks as $routes) {
            $return = $this->matchVariableRouteChunk($path, $routes);
            if ($return) {
                return $return;
            }
        }

        return false;
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

        /** @var Route[] $routes */
        $routes   = [];
        $pattern   = '';
        foreach ($variableRoutes as $route) {
            $numVariables = $route->getParameterCount();
            $pattern .= '|' . $route->getRegexp();
            if ($numVariables < $numGroups && !isset($routes[ $numVariables ])) {
                $routes[ $numVariables ] = $route;
            } else {
                $numGroups = max($numGroups, $numVariables);
                $pattern .= str_repeat('()', $numGroups - $numVariables);
                $routes[ $numGroups++ ] = $route;
            }
        }
        if (!preg_match('#^(?' . $pattern . ')$#', $path, $matched)) {
            return false;
        }
        //Remove the whole match
        array_shift($matched);
        $route = $routes[ count($matched) ];

        $parameterNames = $route->getParameterNames();
        //get the elements of $matched that are actually parameters
        $parameters = array_intersect_key($matched, $parameterNames);
        $matchedParams = array_combine($parameterNames, $parameters);

        return new Match($route, $matchedParams);
    }
}
