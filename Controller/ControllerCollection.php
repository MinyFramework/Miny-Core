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

class ControllerCollection
{
    private $controllers = array();

    public function register($name, $controller)
    {
        if (!is_string($controller)
                && !is_callable($controller)
                && !$controller instanceof \Closure) {
            $type = gettype($controller);
            $message = sprintf('Invalid controller: %s (%s)', $name, $type);
            throw new \InvalidArgumentException($message);
        }
        if (!is_string($controller)) {
            $controller = func_get_args();
            array_shift($controller);
        }
        $this->controllers[$name] = $controller;
    }

    public function getNextName()
    {
        return '_controller_' . count($this->controllers);
    }

    public function getController($class)
    {
        if (!isset($this->controllers[$class])) {
            $controller = $this->getControllerFromClass($class);
        } elseif (is_string($this->controllers[$class])) {
            $controller = $this->getControllerFromClass($this->controllers[$class]);
        } else {
            $factory_params = $this->controllers[$class];
            $callable = array_shift($factory_params);
            $controller = call_user_func_array($callable, $factory_params);
        }
        return $controller;
    }

    public function getControllerFromClass($class)
    {
        if (class_exists($class)) {
            return new $class;
        }
        $fallback = '\Application\Controllers\\' . $class . 'Controller';
        if (class_exists($fallback)) {
            return new $fallback;
        }
        throw new \InvalidArgumentException('Controller not found: ' . $class);
    }

}