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
class AutoLoader {

    private static $map = array();

    public static function init() {
        spl_autoload_register('\Miny\AutoLoader::load');
    }

    public static function register($namespace, $path) {
        self::$map[$namespace] = $path;
    }

    private static function getPathToNamespace($class) {
        $temp = '\\' . $class;
        /*
         * We look for the longest matching namespace so we are trimming
         * from the right.
         */
        while (!isset(self::$map[$temp]) && ($pos = strrpos($temp, '\\')) !== false) {
            $temp = substr($temp, 0, $pos);
        }
        if ($pos === false) {
            throw new \InvalidArgumentException('Class not found: ' . $class);
        }
        $path = substr_replace('\\'.$class, self::$map[$temp], 0, $pos) . '.php';
        return str_replace('\\', DIRECTORY_SEPARATOR, $path);
    }

    public static function load($class) {
        if (isset(self::$map[$class])) {
            $path = self::$map[$class];
        } else if (strpos($class, '\\') !== false) {
            $path = self::getPathToNamespace($class);
        } else {
            throw new \InvalidArgumentException('Class not registered: ' . $class);
        }

        if (!file_exists($path)) {
            throw new \RuntimeException('File not found: ' . $path);
        }
        include_once $path;
        if (!class_exists($class) && !interface_exists($class)) {
            throw new \RuntimeException(
                    sprintf('File %s does not contain class %s.', $path, $class));
        }
    }

    public static function getMap() {
        return self::$map;
    }

}