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
    private $short_urls;

    /**
     * @param RouteCollection $routes
     */
    public function __construct(RouteCollection $routes, $short_urls = true)
    {
        $this->routes     = $routes;
        $this->short_urls = $short_urls;
    }

    /**
     * @param string $route_name
     * @param array $parameters
     * @return string
     * @throws InvalidArgumentException
     * @throws OutOfBoundsException
     */
    public function generate($route_name, array $parameters = array())
    {
        foreach ($this->routes as $name => $route) {
            if ($route_name !== $name) {
                continue;
            }

            $required_params = $route->getParameterNames();
            $missing         = array_diff($required_params, array_keys($parameters));

            if (!empty($missing)) {
                $route_params = $route->getParameters();
                foreach ($missing as $i => $key) {
                    if (isset($route_params[$key])) {
                        $parameters[$key] = $route_params[$key];
                        unset($missing[$i]);
                    }
                }
                if (!empty($missing)) {
                    throw new InvalidArgumentException('Parameters not set: ' . join(', ', $missing));
                }
            }

            return $this->buildPath($route, $parameters);
        }
        throw new OutOfBoundsException('Route not found: ' . $route_name);
    }

    private function buildPath(Route $route, array $parameters)
    {
        $path = $route->getPath();
        krsort($parameters);
        foreach ($parameters as $name => $value) {
            if (strpos($path, ':' . $name) !== false) {
                $path = str_replace(':' . $name, $value, $path);
                unset($parameters[$name]);
            }
        }
        if ($this->short_urls) {
            if (!empty($parameters)) {
                $path .= (strpos($path, '?') === false) ? '?' : '&';
                $path .= http_build_query($parameters, null, '&');
            }
            return $path;
        } else {
            $parameters['path'] = $path;
            return '?' . http_build_query($parameters, null, '&');
        }
    }
}
