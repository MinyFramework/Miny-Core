<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Utils;

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
     * @param array $array
     * @param array $parts
     *
     * @return bool
     */
    public static function existsByPath(array $array, array $parts)
    {
        foreach ($parts as $k) {
            if (!array_key_exists($k, $array)) {
                return false;
            }
            $array = $array[$k];
        }
        return true;
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
     * Inserts $item into $array. The place of $item is determined by $parts.
     *
     * @param array $array
     * @param array $parts
     * @param mixed $item
     */
    public static function setByPath(array &$array, array $parts, $item)
    {
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
     * @param array $parts
     */
    public static function unsetByPath(array &$array, array $parts)
    {
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
