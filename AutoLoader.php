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

/**
 * AutoLoader is a simple autoloader class to be used with the Miny framework.
 *
 * @author Dániel Buga
 */
class AutoLoader
{
    public $extension = '.php';
    private $map = array();
    private $classes = array();

    public function __construct()
    {
        spl_autoload_register(array($this, 'load'));
    }

    private function addNamespacePath($namespace, $path)
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
        if (is_array($namespace)) {
            foreach ($namespace as $ns => $path) {
                $this->addNamespacePath($ns, $path);
            }
        } else {
            if (is_null($path)) {
                throw new \InvalidArgumentException('Missing argument: path');
            }
            $this->addNamespacePath($namespace, $path);
        }
    }

    public function registerClass($class, $path)
    {
        $this->classes[$class] = $path;
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
                throw new ClassNotFoundException('Class not registered: ' . $class);
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
        throw new ClassNotFoundException('Class not found: ' . $class);
    }

    public function load($class)
    {
        if (isset($this->classes[$class])) {
            $path = $this->classes[$class];
            if (!is_file($path)) {
                $message = 'Class file not found: ' . $path;
                throw new ClassNotFoundException($message);
            }
        } else {
            $path = $this->getPathToNamespace($class);
        }

        include_once $path;
        if (!class_exists($class) && !interface_exists($class)) {
            $message = 'File %s does not contain class %s.';
            $message = sprintf($message, $path, $class);
            throw new ClassNotFoundException($message);
        }
    }

    public function getMap()
    {
        return $this->map;
    }

}

class ClassNotFoundException extends \OutOfBoundsException
{

}