<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Router;

use InvalidArgumentException;
use OutOfBoundsException;

class RouteGenerator
{
    /**
     * @var Router
     */
    private $collection;
    private $shortUrlsEnabled;

    /**
     * @param Router $routes
     * @param bool            $short_urls
     */
    public function __construct(Router $routes, $short_urls = true)
    {
        $this->collection       = $routes;
        $this->shortUrlsEnabled = $short_urls;
    }

    /**
     * @param string $routeName
     * @param array  $parameters
     *
     * @return string
     * @throws InvalidArgumentException
     * @throws OutOfBoundsException
     */
    public function generate($routeName, array $parameters = array())
    {
        if (!$this->collection->has($routeName)) {
            throw new OutOfBoundsException(sprintf('Route %s is not found', $routeName));
        }
        $route = $this->collection->getRoute($routeName);

        $required = $route->getParameterNames();
        $missing  = array_diff($required, array_keys($parameters));

        if (!empty($missing)) {
            $parameters = $this->insertDefaultParameterValues($route, $missing, $parameters);
        }

        return $this->buildPath($route->getPath(), $parameters, $required);
    }

    /**
     * @param Route $route
     * @param array $missing
     *
     * @param array $parameters
     *
     * @throws InvalidArgumentException
     * @return array
     */
    private function insertDefaultParameterValues(Route $route, array $missing, array $parameters)
    {
        $values = $route->getDefaultValues();
        foreach ($missing as $i => $key) {
            if (isset($values[$key])) {
                $parameters[$key] = $values[$key];
                unset($missing[$i]);
            }
        }
        if (!empty($missing)) {
            $message = sprintf('Parameters not set: %s', join(', ', $missing));
            throw new InvalidArgumentException($message);
        }

        return $parameters;
    }

    private function buildPath($path, array $parameters, array $parameterNames)
    {
        $replace = array();
        foreach($parameterNames as $name) {
            $token = '{' . $name . '}';
            $replace[$token] = $parameters[$name];
            unset($parameters[$name]);
        }
        $path = strtr($path, $replace);
        if ($this->shortUrlsEnabled) {
            if (!empty($parameters)) {
                $path .= (strpos($path, '?') === false) ? '?' : '&';
                $path .= http_build_query($parameters, null, '&');
            }
        } else {
            $parameters = array('path' => $path) + $parameters;

            $path = '?' . http_build_query($parameters, null, '&');
        }

        return $path;
    }
}
