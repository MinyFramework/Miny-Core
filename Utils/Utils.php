<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Utils;

use InvalidArgumentException;
use Miny\Utils\Exceptions\AssertationException;
use ReflectionClass;

/**
 * @author Dániel Buga
 */
class Utils
{

    /**
     * Asserts that $expression is true or throws an exception.
     *
     * @param mixed $expression
     * @param string $on_failure
     * @throws AssertationException
     */
    public static function assert($expression, $on_failure = 'Assertation failed')
    {
        if (!$expression) {
            throw new AssertationException($on_failure);
        }
    }

    /**
     * @param string $class
     * @param array $arguments
     * @return object
     */
    public static function instantiate($class, array $arguments = array())
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException('Class not found: ' . $class);
        }
        switch (count($arguments)) {
            case 0:
                return new $class;

            case 1:
                return new $class(current($arguments));

            case 2:
                list($arg1, $arg2) = $arguments;
                return new $class($arg1, $arg2);

            case 3:
                list($arg1, $arg2, $arg3) = $arguments;
                return new $class($arg1, $arg2, $arg3);

            case 4:
                list($arg1, $arg2, $arg3, $arg4) = $arguments;
                return new $class($arg1, $arg2, $arg3, $arg4);

            default:
                $ref = new ReflectionClass($class);
                return $ref->newInstanceArgs($arguments);
        }
    }

    /**
     * Walks through the given $array and returns a reference to the value represented by $path.
     *
     * @param array $array
     * @param array $parts
     * @param bool $create
     *
     * @return mixed
     *
     * @throws OutOfBoundsException
     */
    public static function &findByPath(array &$array, array $parts, $create = false)
    {
        foreach ($parts as $k) {
            if (!array_key_exists($k, $array)) {
                if ($create) {
                    $array[$k] = array();
                } else {
                    throw new OutOfBoundsException('Array key not found: ' . implode(':', $parts));
                }
            }
            $array = & $array[$k];
        }
        return $array;
    }

    /**
     * Merge two arrays recursively.
     *
     * @param array $array1
     * @param array $array2
     * @param bool $overwrite Overwrite value if it exists in $array1.
     *
     * @return array
     */
    public static function merge(array $array1, array $array2, $overwrite = true)
    {
        foreach ($array2 as $key => $value) {
            if (isset($array1[$key])) {
                if (is_array($array1[$key]) && is_array($value)) {
                    $array1[$key] = self::merge($array1[$key], $value, $overwrite);
                } elseif ($overwrite) {
                    $array1[$key] = $value;
                }
            } else {
                $array1[$key] = $value;
            }
        }
        return $array1;
    }
}
