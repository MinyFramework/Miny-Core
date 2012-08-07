<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny;

use OutOfBoundsException;

/**
 * AutoLoader is a simple autoloader class to be used with the Miny framework.
 *
 * @author Dániel Buga
 */
class AutoLoader
{
    private $map = array();

    public function __construct(array $map = array())
    {
        spl_autoload_register(array($this, 'load'));
        $this->register($map);
    }

    public function register($namespace, $path = NULL)
    {
        if (is_array($namespace)) {
            foreach ($namespace as $ns => $path) {
                $this->register($ns, $path);
            }
        } elseif (is_array($path)) {
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
            $path .= substr($class, $pos - 1) . '.php';
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