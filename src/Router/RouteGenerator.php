<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Router;

use InvalidArgumentException;

class RouteGenerator
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @var bool
     */
    private $shortUrlsEnabled;

    /**
     * @param Router $router
     * @param bool   $shortUrls
     */
    public function __construct(Router $router, $shortUrls = true)
    {
        $this->router           = $router;
        $this->shortUrlsEnabled = $shortUrls;
    }

    /**
     * @param string $routeName
     * @param array  $parameters
     *
     * @return string
     * @throws InvalidArgumentException
     */
    public function generate($routeName, array $parameters = array())
    {
        $route = $this->router->getRoute($routeName);

        $required = $route->getParameterPatterns();
        $missing  = array_diff_key($required, $parameters);

        if (!empty($missing)) {
            $parameters = $this->insertDefaultParameterValues($route, array_keys($missing), $parameters);
        }

        return $this->buildPath($route->getPath(), $parameters, array_keys($required));
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
        foreach ($parameterNames as $name) {
            $replace['{' . $name . '}'] = $parameters[$name];
            unset($parameters[$name]);
        }
        $path = strtr($path, $replace);
        if ($this->shortUrlsEnabled) {
            if (empty($parameters)) {
                return $path;
            }
            $path .= (strpos($path, '?') === false) ? '?' : '&';

        } else {
            $parameters = array('path' => $path) + $parameters;
            $path       = '?';
        }

        return $path . http_build_query($parameters, null, '&');
    }
}
