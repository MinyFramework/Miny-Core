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
    const PARAMETER_WITH_PATTERN = '/{(\w+)(?::(.*?))?}/';

    private $defaultPattern;

    public function __construct($defaultPattern = '[^/]+')
    {
        $this->defaultPattern = $defaultPattern;
    }

    private function makePlaceholder($name)
    {
        return '{' . $name . '}';
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
            self::PARAMETER_WITH_PATTERN,
            function ($matches) use ($parser, $route) {
                if (!isset($matches[2])) {
                    $matches[2] = $this->defaultPattern;
                }
                $route->specify($matches[1], $matches[2]);

                return $parser->makePlaceholder($matches[1]);
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
            $keys[]     = preg_quote($this->makePlaceholder($key), '#');
            $patterns[] = '('.$pattern.')';
        }
        $uri    = preg_quote($uri, '#');
        $regexp = str_replace($keys, $patterns, $uri);

        return $regexp;
    }
}
