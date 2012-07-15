<?php

/**
 * This file is part of the Miny framework.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version accepted by the author in accordance with section
 * 14 of the GNU General Public License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   Miny/Routing
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Routing;

class Router extends RouteCollection
{
    private $matcher;
    private $generator;

    public function __construct()
    {
        $this->matcher = new RouteMatcher($this);
        $this->generator = new RouteGenerator($this);
    }

    public function getMatcher()
    {
        return $this->matcher;
    }

    public function getGenerator()
    {
        return $this->generator;
    }

    public function route(Route $route, $name = NULL)
    {
        $this->addRoute($route, $name);
    }

    public function resource(Resources $resource)
    {
        $this->merge($resource);
    }

    public function match($path, $method = NULL)
    {
        return $this->getMatcher()->match($path, $method);
    }

    public function generate($route_name, array $parameters = array())
    {
        return $this->getGenerator()->generate($route_name, $parameters);
    }

}