<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Router;

abstract class AbstractRouteParser
{
    /**
     * @param string $route
     * @param null   $method
     *
     * @return Route
     */
    abstract public function parse($route, $method = null);
}
