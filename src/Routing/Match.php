<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Routing;

class Match
{
    /**
     * @var Route
     */
    private $route;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param Route $route
     * @param array $parameters
     */
    public function __construct(Route $route, array $parameters = array())
    {
        $this->route      = $route;
        $this->parameters = $parameters + $route->getParameters();
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return Route
     */
    public function getRoute()
    {
        return $this->route;
    }
}
