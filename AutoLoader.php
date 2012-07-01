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
    private static $map = array();

    public static function init()
    {
        spl_autoload_register('\Miny\AutoLoader::load');
    }

    private static function addNamespacePath($namespace, $path)
    {
        if (!isset(self::$map[$namespace])) {
            self::$map[$namespace] = array($path);
        } else {
            self::$map[$namespace][] = $path;
        }
    }

    public static function register($namespace, $path = NULL)
    {
        if (is_array($namespace)) {
            foreach ($namespace as $ns => $path) {
                self::addNamespacePath($ns, $path);
            }
        } else {
            if (is_null($path)) {
                throw new \InvalidArgumentException('Missing argument: path');
            }
            self::addNamespacePath($namespace, $path);
        }
    }

    private static function getPathToNamespace($class)
    {
        $temp = '\\' . $class;
        /*
         * We look for the longest matching namespace so we are trimming
         * from the right.
         */
        while (!isset(self::$map[$temp])) {
            if (($pos = strrpos($temp, '\\')) === false) {
                break;
            }
            $temp = substr($temp, 0, $pos);
        }
        if ($pos === false) {
            throw new ClassNotFoundException('Class not registered: ' . $class);
        }
        foreach (self::$map[$temp] as $part) {
            $path = substr_replace('\\' . $class, $part, 0, $pos);
            $path .= '.php';
            $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
            if (file_exists($path)) {
                return $path;
            }
        }
        throw new ClassNotFoundException('Class file not found: ' . $class);
    }

    public static function load($class)
    {
        if (isset(self::$map[$class])) {
            $path = self::$map[$class];
            if (!file_exists($path)) {
                $message = 'Class file not found: ' . $path;
                throw new ClassNotFoundException($message);
            }
        } elseif (strpos($class, '\\') !== false) {
            $path = self::getPathToNamespace($class);
        } else {
            $message = 'Class not registered: ' . $class;
            throw new ClassNotFoundException($message);
        }

        include_once $path;
        if (!class_exists($class) && !interface_exists($class)) {
            $message = 'File %s does not contain class %s.';
            $message = sprintf($message, $path, $class);
            throw new ClassNotFoundException($message);
        }
    }

    public static function getMap()
    {
        return self::$map;
    }

}

class ClassNotFoundException extends \OutOfBoundsException
{

}