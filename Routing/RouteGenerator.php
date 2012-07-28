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

class RouteGenerator
{
    private $routes;

    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    public function generate($route_name, array $parameters = array())
    {
        foreach ($this->routes as $name => $route) {
            if ($route_name !== $name) {
                continue;
            }

            $required_params = $route->getParameterNames();
            $missing = array_diff($required_params, array_keys($parameters));

            if (!empty($missing)) {
                $route_params = $route->getParameters();
                foreach ($missing as $i => $key) {
                    if (isset($route_params[$key])) {
                        $parameters[$key] = $route_params[$key];
                        unset($missing[$i]);
                    }
                }
                if (!empty($missing)) {
                    $message = 'Parameters not set: ' . join(', ', $missing);
                    throw new \InvalidArgumentException($message);
                }
            }

            $path = $route->getPath();
            $glue = (strpos($path, '?') === false) ? '?' : '&';
            foreach ($parameters as $name => $value) {
                if (strpos($path, ':' . $name) !== false) {
                    $path = str_replace(':' . $name, $value, $path);
                } else {
                    $path .= $glue . $name . '=' . $value;
                    $glue = '&';
                }
            }
            return $path;
        }
        throw new \OutOfBoundsException('Route not found: ' . $route_name);
    }

}