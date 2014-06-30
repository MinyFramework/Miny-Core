<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Router;

class RouteParser extends AbstractRouteParser
{
    private $defaultPattern;

    public function __construct($defaultPattern = '[^/]+')
    {
        $this->defaultPattern = $defaultPattern;
    }

    public function getDefaultPattern()
    {
        return $this->defaultPattern;
    }

    /**
     * @inheritdoc
     */
    public function parse($uri, $method = Route::METHOD_ALL)
    {
        $route = new Route;
        $route->setMethod($method);

        $defaultPattern = $this->defaultPattern;

        $uri = preg_replace_callback(
            '/{(\w+)(?::(.*?))?}/',
            function ($matches) use ($defaultPattern, $route) {
                if (!isset($matches[2])) {
                    $matches[2] = $defaultPattern;
                }
                $route->specify($matches[1], $matches[2]);

                return '{' . $matches[1] . '}';
            },
            $uri
        );
        $route->setPath($uri);
        if ($route->getParameterCount() === 0) {
            return $route;
        }
        $route->setRegexp($this->createRegexp($uri, $route));

        return $route;
    }

    /**
     * @param       $uri
     * @param Route $route
     *
     * @return mixed
     */
    private function createRegexp($uri, Route $route)
    {
        $patterns = array();
        foreach ($route->getParameterPatterns() as $key => $pattern) {
            $patterns['\\{' . $key . '\\}'] = '(' . $pattern . ')';
        }
        $uri = preg_quote($uri, '#');

        return strtr($uri, $patterns);
    }
}
