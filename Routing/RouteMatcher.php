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

class RouteMatcher
{
    private $routes;

    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    public function match($path, $method = NULL)
    {
        foreach ($this->routes as $route) {
            $route_method = $route->getMethod();
            if ($method !== NULL && $route_method !== NULL && $method !== $route_method) {
                continue;
            }

            if ($route->isStatic()) {
                if ($path != $route->getPath()) {
                    continue;
                }
                return new Match($route);
            }

            $matched = array();
            if (preg_match('#^' . $route->getRegex() . '$#Du', $path, $matched)) {
                $matched_params = array();
                $parameter_count = $route->getParameterCount();
                for ($i = 1; $i < $parameter_count; ++$i) {
                    $matched_params[$route->getParameterName($i - 1)] = $matched[$i];
                }
                return new Match($route, $matched_params);
            }
        }
        return false;
    }

}