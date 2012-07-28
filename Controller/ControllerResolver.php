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
 * @package   Miny/Controller
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Controller;

use \Miny\HTTP\Request;
use \Miny\Application\Application;

class ControllerResolver
{
    private $application;
    private $collection;

    public function __construct(Application $application, ControllerCollection $collection = NULL)
    {
        $this->application = $application;
        $this->collection = $collection;
    }

    public function resolve($class, $action, Request $request)
    {
        $controller = $this->collection->getController($class);
        if (is_string($controller)) {
            $controller = new $controller($this->application);
        } elseif (is_array($controller)) {
            if (isset($controller[0]) && is_callable($controller[0])) {
                $callback = array_shift($controller);
                $controller = call_user_func_array($callback, $controller);
            }
        }
        if ($controller instanceof Controller) {
            return $controller->run($action, $request);
        } elseif ($controller instanceof \Closure) {
            return $controller($request, $action);
        }
        throw new \InvalidArgumentException('Invalid controller: ' . $class);
    }

}