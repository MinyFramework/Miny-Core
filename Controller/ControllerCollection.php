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
        if ($controller instanceof \Closure || $controller instanceof Controller || is_string($controller)) {
            $this->controllers[$name] = $controller;
        } elseif (is_callable($controller)) {
            $controller = func_get_args();
            array_shift($controller);
            $this->controllers[$name] = $controller;
        } else {
            $type = gettype($controller);
            throw new \InvalidArgumentException(sprintf('Invalid controller: %s (%s)', $name, $type));
        }
    }

    public function getNextName()
    {
        return '_controller_' . count($this->controllers);
    }

    public function getController($class)
    {
        if (isset($this->controllers[$class])) {
            if (!is_string($this->controllers[$class])) {
                return $this->controllers[$class];
            }
            $class = $this->controllers[$class];
        }
        if (!class_exists($class)) {
            $class = '\Application\Controllers\\' . ucfirst($class) . 'Controller';
            if (!class_exists($class)) {
                throw new \UnexpectedValueException('Class not exists: ' . $class);
            }
        }
        if (!is_subclass_of($class, __NAMESPACE__ . '\Controller')) {
            throw new \UnexpectedValueException('Class does not extend Controller: ' . $class);
        }
        return $class;
    }

}