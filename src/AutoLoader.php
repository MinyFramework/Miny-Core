<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny;

/**
 * AutoLoader is a simple PSR-4 class loader class to be used with the Miny Framework.
 *
 * @author Dániel Buga
 */
class AutoLoader
{
    /**
     * @var array[]
     */
    private $namespaceMap = array();
    private $maxNameSpaceLength = 0;
    private $classMap = array();

    /**
     * @param array $map
     */
    public function __construct(array $map = array())
    {
        spl_autoload_register(array($this, 'load'));
        $this->register($map);
    }

    public function addClass($class, $path)
    {
        $this->classMap[$class] = $path;
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
            $length = strlen($namespace);
            if (strpos($namespace, '\\') === 0) {
                ++$length;
            }
            if ($this->maxNameSpaceLength < $length) {
                $this->maxNameSpaceLength = $length;
            }
            if (!isset($this->namespaceMap[$namespace])) {
                $this->namespaceMap[$namespace] = array();
            }
            if (is_array($path)) {
                foreach ($path as $new_path) {
                    $this->namespaceMap[$namespace][] = $new_path;
                }
            } else {
                $this->namespaceMap[$namespace][] = $path;
            }
        }
    }

    private function findFile($class)
    {
        if (isset($this->classMap[$class])) {
            return $this->classMap[$class];
        }

        $temp = substr('\\' . $class, 0, $this->maxNameSpaceLength + 1);
        // We look for the longest matching namespace so we are trimming from the right.
        while (!isset($this->namespaceMap[$temp])) {
            if (($pos = strrpos($temp, '\\')) === false) {
                // The class/namespace was not registered.
                return false;
            }
            $temp = substr($temp, 0, $pos);
        }
        if (isset($pos)) {
            $className = substr($class, $pos - 1);
            $subPath   = strtr($className, '\\', DIRECTORY_SEPARATOR) . '.php';
        } else {
            $subPath = '';
        }
        foreach ($this->namespaceMap[$temp] as $path) {
            $path .= $subPath;
            if (is_file($path)) {
                return $path;
            }
        }

        $this->classMap[$class] = false;

        return false;
    }

    /**
     * @param string $class
     */
    public function load($class)
    {
        if ($file = $this->findFile($class)) {
            includeFile($file);
        }
    }
}

function includeFile($path)
{
    include $path;
}
