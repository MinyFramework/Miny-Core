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
    private $route_prefix;
    private $route_suffix;
    private $default_parameters;
    private $resources = array();
    private $resources_built = false;

    public function __construct($prefix = NULL, $suffix = NULL,
                                array $parameters = array())
    {
        $this->matcher = new RouteMatcher($this);
        $this->generator = new RouteGenerator($this);
        $this->route_prefix = $prefix;
        $this->route_suffix = $suffix;
        $this->default_parameters = $parameters;
    }

    public function getMatcher()
    {
        return $this->matcher;
    }

    public function getGenerator()
    {
        return $this->generator;
    }

    public function root(array $parameters = array(), $prefix = true,
                         $suffix = false)
    {
        $route = new Route('', 'GET', $parameters);
        $this->route($route, 'root', $prefix, $suffix);
    }

    public function route(Route $route, $name, $prefix = true, $suffix = true)
    {
        if (!is_null($this->route_prefix) || !is_null($this->route_suffix)) {
            $path = $route->getPath();
            if ($prefix) {
                $path = $this->route_prefix . $path;
            }
            if ($suffix) {
                $path .= $this->route_suffix;
            }
            $route->setPath($path);
            $route->addParameters($this->default_parameters);
        }
        $this->addRoute($route, $name);
        return $route;
    }

    public function resource($name, array $parameters = array(),
                             $singular = false)
    {
        $parameters = $parameters + $this->default_parameters;
        if ($singular) {
            $resource = new Resource($name, $parameters);
        } else {
            $resource = new Resources($name, $parameters);
        }
        $this->resources[] = $resource;
        return $resource;
    }

    private function buildResources()
    {
        if ($this->resources_built) {
            return;
        }
        $this->resources_built = true;
        foreach ($this->resources as $resource) {
            foreach ($resource as $name => $route) {
                $this->route($route, $name);
            }
        }
    }

    public function match($path, $method = NULL)
    {
        $this->buildResources();
        return $this->getMatcher()->match($path, $method);
    }

    public function generate($route_name, array $parameters = array())
    {
        $this->buildResources();
        return $this->getGenerator()->generate($route_name, $parameters);
    }

}