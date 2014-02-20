<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Routing;

use InvalidArgumentException;
use OutOfBoundsException;

class RouteGenerator
{
    /**
     * @var RouteCollection
     */
    private $routes;
    private $shortUrlsEnabled;

    /**
     * @param RouteCollection $routes
     * @param bool            $short_urls
     */
    public function __construct(RouteCollection $routes, $short_urls = true)
    {
        $this->routes           = $routes;
        $this->shortUrlsEnabled = $short_urls;
    }

    /**
     * @param string $route_name
     * @param array  $parameters
     *
     * @return string
     * @throws InvalidArgumentException
     * @throws OutOfBoundsException
     */
    public function generate($route_name, array $parameters = array())
    {
        /** @var $route Route */
        foreach ($this->routes as $name => $route) {
            if ($route_name !== $name) {
                continue;
            }

            $required_params = $route->getParameterNames();
            $missing         = array_diff($required_params, array_keys($parameters));

            if (!empty($missing)) {
                $parameters = $this->insertDefaultParameterValues($route, $missing, $parameters);
            }

            return $this->buildPath($route, $parameters);
        }
        throw new OutOfBoundsException('Route not found: ' . $route_name);
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
        $route_params = $route->getParameters();
        foreach ($missing as $i => $key) {
            if (isset($route_params[$key])) {
                $parameters[$key] = $route_params[$key];
                unset($missing[$i]);
            }
        }
        if (!empty($missing)) {
            $message = sprintf('Parameters not set: %s', join(', ', $missing));
            throw new InvalidArgumentException($message);
        }

        return $parameters;
    }

    private function buildPath(Route $route, array $parameters)
    {
        $path = $route->getPath();
        krsort($parameters);
        foreach ($parameters as $name => $value) {
            $token = '{' . $name . '}';
            if (strpos($path, $token) !== false) {
                $path = str_replace($token, $value, $path);
                unset($parameters[$name]);
            }
        }
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
