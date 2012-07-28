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

use InvalidArgumentException;
use OutOfBoundsException;

/**
 * AutoLoader is a simple autoloader class to be used with the Miny framework.
 *
 * @author Dániel Buga
 */
class AutoLoader
{
    public $extension = '.php';
    private $map = array();

    public function __construct(array $map = array())
    {
        spl_autoload_register(array($this, 'load'));
        $this->register($map);
    }

    private function addNamespace($namespace, $path)
    {
        if (is_array($path)) {
            if (isset($this->map[$namespace])) {
                $path = array_merge($this->map[$namespace], $path);
            }
            $this->map[$namespace] = $path;
        } else {
            if (!isset($this->map[$namespace])) {
                $this->map[$namespace] = array();
            }
            $this->map[$namespace][] = $path;
        }
    }

    public function register($namespace, $path = NULL)
    {
        if (is_null($path)) {
            if (!is_array($namespace)) {
                throw new InvalidArgumentException('Argument must be an array');
            }
            foreach ($namespace as $ns => $path) {
                $this->addNamespace($ns, $path);
            }
        } else {
            $this->addNamespace($namespace, $path);
        }
    }

    private function getPathToNamespace($class)
    {
        $temp = '\\' . $class;
        /*
         * We look for the longest matching namespace so we are trimming
         * from the right.
         */
        while (!isset($this->map[$temp])) {
            if (($pos = strrpos($temp, '\\')) === false) {
                return;
            }
            $temp = substr($temp, 0, $pos);
        }
        foreach ($this->map[$temp] as $path) {
            $path .= substr($class, $pos - 1) . $this->extension;
            $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
            if (is_file($path)) {
                return $path;
            }
        }
    }

    public function load($class)
    {
        $path = $this->getPathToNamespace($class);
        if (!$path) {
            return;
        }
        include_once $path;
        if (!class_exists($class) && !interface_exists($class)) {
            throw new ClassNotFoundException($path, $class);
        }
    }

}

class ClassNotFoundException extends OutOfBoundsException
{
    public function __construct($path, $class)
    {
        parent::__construct(sprintf('File %s does not contain class %s.', $path, $class));
    }

}