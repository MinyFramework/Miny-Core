<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Utils;

use InvalidArgumentException;
use OutOfBoundsException;

/**
 * ArrayUtils provides useful tools to work with arrays.
 *
 * @author Dániel Buga <bugadani@gmail.com>
 */
class ArrayUtils
{

    /**
     * @param string|array $path
     * @return array
     * 
     * @throws InvalidArgumentException
     */
    private static function createPath($path)
    {
        if (is_array($path)) {
            return $path;
        }
        if (is_string($path)) {
            if (strpos($path, ':') === false) {
                return array($path);
            } else {
                return explode(':', $path);
            }
        }
        throw new InvalidArgumentException('Path must be an array or a string.');
    }

    /**
     * Determines whether the value represented by $path exists in $array.
     *
     * @param array $array
     * @param array|string $parts An array containing the keys or a string delimited by :
     *
     * @return bool
     */
    public static function existsByPath(array $array, $parts)
    {
        $parts = self::createPath($parts);
        foreach ($parts as $k) {
            if (!is_array($array) || !array_key_exists($k, $array)) {
                return false;
            }
            $array = $array[$k];
        }
        return true;
    }

    /**
     * Walks through the given $array and returns the value represented by $path. Returns $default if the requested
     * element does not exist.
     *
     * @param array $array
     * @param array|string $parts An array containing the keys or a string delimited by :
     * @param mixed $default
     *
     * @return mixed
     */
    public static function getByPath(array $array, $parts, $default = null)
    {
        $parts = self::createPath($parts);
        foreach ($parts as $k) {
            if (!array_key_exists($k, $array)) {
                return $default;
            }
            $array = $array[$k];
        }
        return $array;
    }

    /**
     * Walks through the given $array and returns a reference to the value represented by $path.
     *
     * @param array $array
     * @param array|string $parts An array containing the keys or a string delimited by :
     * @param bool $create
     *
     * @return mixed
     *
     * @throws OutOfBoundsException
     */
    public static function &findByPath(array &$array, $parts, $create = false)
    {
        $parts = self::createPath($parts);
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
     * Inserts $item into $array. The place of $item is determined by $parts.
     *
     * @param array $array
     * @param array|string $parts An array containing the keys or a string delimited by :
     * @param mixed $item
     */
    public static function setByPath(array &$array, $parts, $item)
    {
        $parts = self::createPath($parts);
        foreach ($parts as $k) {
            if (!array_key_exists($k, $array)) {
                $array[$k] = array();
            }
            $array = & $array[$k];
        }
        $array = $item;
    }

    /**
     * Removed the item from $array that is represented by $parts.
     *
     * @param array $array
     * @param array|string $parts An array containing the keys or a string delimited by ":"
     */
    public static function unsetByPath(array &$array, $parts)
    {
        $parts    = self::createPath($parts);
        $last_key = array_pop($parts);
        foreach ($parts as $k) {
            if (!array_key_exists($k, $array)) {
                $array[$k] = array();
            }
            $array = & $array[$k];
        }
        unset($array[$last_key]);
    }

    /**
     * Merge two arrays recursively.
     *
     * @param array $array1
     * @param array $array2
     * @param bool $overwrite Overwrite value if it exists in $array1.
     *
     * @return array The merged array
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
