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

    /**
     * @param string $class
     * @return string
     */
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

    /**
     * @param string $class
     * @throws ClassNotFoundException
     */
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
