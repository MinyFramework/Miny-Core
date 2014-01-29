<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Utils;

use ArrayAccess;
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
     * Determines whether the value represented by $path exists in $array.
     *
     * @param array|ArrayAccess $array
     * @param array|string      $parts An array containing the keys or a string delimited by :
     *
     * @throws InvalidArgumentException
     * @return bool
     */
    public static function existsByPath($array, $parts)
    {
        if (!is_array($array) && !$array instanceof ArrayAccess) {
            throw new InvalidArgumentException('ArrayUtils::existsByPath expects an array or an ArrayAccess object.');
        }
        if (!is_array($parts)) {
            $parts = explode(':', $parts);
        }
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
     * @param array|ArrayAccess $array
     * @param array|string      $parts An array containing the keys or a string delimited by :
     * @param mixed             $default
     *
     * @throws InvalidArgumentException
     * @return mixed
     */
    public static function getByPath($array, $parts, $default = null)
    {
        if (!is_array($array) && !$array instanceof ArrayAccess) {
            throw new InvalidArgumentException('ArrayUtils::getByPath expects an array or an ArrayAccess object.');
        }
        if (!is_array($parts)) {
            $parts = explode(':', $parts);
        }
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
     * @param array|ArrayAccess $array
     * @param array|string      $parts An array containing the keys or a string delimited by :
     * @param bool              $create
     *
     * @return mixed
     *
     * @throws OutOfBoundsException
     */
    public static function &findByPath(array &$array, $parts, $create = false)
    {
        if (!is_array($parts)) {
            $parts = explode(':', $parts);
        }
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
     * @param array        $array
     * @param array|string $parts An array containing the keys or a string delimited by :
     * @param mixed        $item
     */
    public static function setByPath(array &$array, $parts, $item)
    {
        if (!is_array($parts)) {
            $parts = explode(':', $parts);
        }
        foreach ($parts as $k) {
            if($k === null || $k === '') {
                $k = count($array);
            }
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
     * @param array        $array
     * @param array|string $parts An array containing the keys or a string delimited by ":"
     */
    public static function unsetByPath(array &$array, $parts)
    {
        if (!is_array($parts)) {
            $parts = explode(':', $parts);
        }
        $last_key = array_pop($parts);
        foreach ($parts as $k) {
            if (!array_key_exists($k, $array)) {
                return;
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
     * @param bool  $overwrite Overwrite value if it exists in $array1.
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

    public static function implodeIfArray($data, $glue)
    {
        if (is_array($data)) {
            return implode($glue, $data);
        }
        return $data;
    }
}
