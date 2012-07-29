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
 * @package   Miny
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny;

use BadMethodCallException;
use Closure;
use InvalidArgumentException;

class Extendable
{
    private $plugins = array();

    public function addMethod($method, $callback)
    {
        if (!is_callable($callback) && !$callback instanceof Closure) {
            throw new InvalidArgumentException('Callback must be callable');
        }
        $this->plugins[$method] = $callback;
    }

    public function addMethods($object, array $method_aliasses = array())
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException('First argument must be an object');
        }
        foreach ($method_aliasses as $alias => $method) {
            if (!method_exists($object, $method)) {
                throw new InvalidArgumentException('Method not found: ' . $method);
            }
            if (is_numeric($alias)) {
                $alias = $method;
            }
            $this->plugins[$alias] = array($object, $method);
        }
    }

    public function __call($method, $args)
    {
        if (!isset($this->plugins[$method])) {
            throw new BadMethodCallException('Method not found: ' . $method);
        }
        return call_user_func_array($this->plugins[$method], $args);
    }

}