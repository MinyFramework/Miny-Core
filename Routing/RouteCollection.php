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

use ArrayIterator;
use IteratorAggregate;
use OutOfBoundsException;

class RouteCollection implements IteratorAggregate
{
    private $routes = array();

    public function merge(RouteCollection $collection)
    {
        $this->routes = array_merge($this->routes, $collection->routes);
    }

    public function addRoute(Route $route, $name = NULL)
    {
        if (is_null($name)) {
            $this->routes[] = $route;
        } else {
            $this->routes[$name] = $route;
        }
    }

    public function addRoutes(array $routes)
    {
        foreach ($routes as $name => $route) {
            $this->addRoute($name, $route);
        }
    }

    public function getRoute($name)
    {
        if (!isset($this->routes[$name])) {
            throw new OutOfBoundsException('Route not set: ' . $name);
        }
        return $this->routes[$name];
    }

    public function getIterator()
    {
        return new ArrayIterator($this->routes);
    }

}