<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny;

/**
 * AutoLoader is a simple autoloader class to be used with the Miny framework.
 *
 * @author Dániel Buga
 */
class AutoLoader
{
    /**
     * @var string[]
     */
    private $map = array();

    /**
     * @param array $map
     */
    public function __construct(array $map = array())
    {
        spl_autoload_register(array($this, 'load'));
        $this->register($map);
    }

    /**
     * @param string $namespace
     * @param string $path
     */
    public function register($namespace, $path = null)
    {
        if (is_array($namespace)) {
            foreach ($namespace as $ns => $path) {
                $this->register($ns, $path);
            }
        } else {
            if (!isset($this->map[$namespace])) {
                $this->map[$namespace] = array();
            }
            if (is_array($path)) {
                foreach ($path as $new_path) {
                    $this->map[$namespace][] = realpath($new_path);
                }
            } else {
                $this->map[$namespace][] = realpath($path);
            }
        }
    }

    /**
     * @param string $class
     */
    public function load($class)
    {
        $temp = '\\' . $class;

        // We look for the longest matching namespace so we are trimming from the right.
        while (!isset($this->map[$temp])) {
            if (($pos = strrpos($temp, '\\')) === false) {
                // The class/namespace was not registered.
                return;
            }
            $temp = substr($temp, 0, $pos);
        }
        $classname = substr($class, $pos - 1);
        $subpath   = strtr($classname, '\\', DIRECTORY_SEPARATOR) . '.php';
        foreach ($this->map[$temp] as $path) {
            $path .= $subpath;
            if (is_file($path)) {
                include_once $path;
                return;
            }
        }
    }
}
