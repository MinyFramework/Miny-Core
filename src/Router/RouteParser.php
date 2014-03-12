<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
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

    /**
     * @inheritdoc
     */
    public function parse($uri, $method = null)
    {
        $route = new Route;
        $route->setMethod($method);

        $parser = $this;
        $uri    = preg_replace_callback(
            '/{(\w+)(?::(.*?))?}/',
            function ($matches) use ($parser, $route) {
                if (!isset($matches[2])) {
                    $matches[2] = $this->defaultPattern;
                }
                $route->specify($matches[1], $matches[2]);

                return '{' . $matches[1] . '}';
            },
            $uri
        );
        $route->setPath($uri);
        if ($route->isStatic()) {
            return $route;
        }
        $regexp = $this->createRegexp($uri, $route);
        $route->setRegexp($regexp);

        return $route;
    }

    /**
     * @param $uri
     * @param Route $route
     *
     * @return mixed
     */
    private function createRegexp($uri, Route $route)
    {
        $keys     = array();
        $patterns = array();
        foreach ($route->getParameterPatterns() as $key => $pattern) {
            $keys[] = '\\{' . $key . '\\}';
            $patterns[] = '('.$pattern.')';
        }
        $uri    = preg_quote($uri, '#');
        $regexp = str_replace($keys, $patterns, $uri);

        return $regexp;
    }
}
