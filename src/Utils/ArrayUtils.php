<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Utils;

/**
 * ArrayUtils provides useful tools to work with arrays.
 *
 * @author Dániel Buga <bugadani@gmail.com>
 */
class ArrayUtils
{
    public static $delimiter = ':';

    public static function isArray($array)
    {
        return is_array($array) || $array instanceof \ArrayAccess;
    }

    private static function arrayHasKey($array, $key)
    {
        if (is_array($array)) {
            return array_key_exists($key, $array);
        } elseif ($array instanceof \ArrayAccess) {
            return isset($array[$key]);
        } else {
            return false;
        }
    }

    /**
     * Determines whether the value represented by $path exists in $array.
     *
     * @param array|\ArrayAccess $array
     * @param array|string       $parts An array containing the keys or a string delimited by :
     *
     * @throws \InvalidArgumentException
     * @return bool
     */
    public static function exists($array, $parts)
    {
        if (!is_array($array) && !$array instanceof \ArrayAccess) {
            throw new \InvalidArgumentException('ArrayUtils::existsByPath expects an array or an ArrayAccess object.');
        }
        foreach (self::explodePath($parts) as $k) {
            if (!static::arrayHasKey($array, $k)) {
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
     * @param array|\ArrayAccess $array
     * @param array|string       $parts An array containing the keys or a string delimited by :
     * @param mixed              $default
     *
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public static function get($array, $parts, $default = null)
    {
        if (!self::isArray($array)) {
            throw new \InvalidArgumentException('ArrayUtils::getByPath expects an array or an ArrayAccess object.');
        }
        foreach (self::explodePath($parts) as $k) {
            if (!static::arrayHasKey($array, $k)) {
                return $default;
            }
            $array = $array[$k];
        }

        return $array;
    }

    /**
     * Walks through the given $array and returns a reference to the value represented by $path.
     *
     * @param array|\ArrayAccess $array
     * @param array|string       $parts An array containing the keys or a string delimited by :
     * @param bool               $create
     *
     * @throws \OutOfBoundsException
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    public static function &find(&$array, $parts, $create = false)
    {
        if (!self::isArray($array)) {
            throw new \InvalidArgumentException('ArrayUtils::getByPath expects an array or an ArrayAccess object.');
        }
        foreach (self::explodePath($parts) as $k) {
            if (!static::arrayHasKey($array, $k)) {
                if (!$create) {
                    $key = self::implodeIfArray($parts, self::$delimiter);
                    throw new \OutOfBoundsException("Array key not found: {$key}");
                }
                $array[$k] = [];
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
     *
     * @throws \InvalidArgumentException
     */
    public static function set(array &$array, $parts, $item)
    {
        if (!self::isArray($array)) {
            throw new \InvalidArgumentException('ArrayUtils::getByPath expects an array or an ArrayAccess object.');
        }
        foreach (self::explodePath($parts) as $k) {
            if ($k === null || $k === '') {
                $k = count($array);
            }
            if (!static::arrayHasKey($array, $k)) {
                $array[$k] = [];
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
     *
     * @throws \InvalidArgumentException
     */
    public static function remove(array &$array, $parts)
    {
        if (!self::isArray($array)) {
            throw new \InvalidArgumentException('ArrayUtils::getByPath expects an array or an ArrayAccess object.');
        }
        $parts = self::explodePath($parts);
        $last_key = array_pop($parts);
        foreach ($parts as $k) {
            if (!static::arrayHasKey($array, $k)) {
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
                if (self::isArray($array1[$key]) && self::isArray($value)) {
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

    /**
     * @param $parts
     * @return array
     */
    private static function explodePath($parts)
    {
        if (!is_array($parts)) {
            $parts = explode(self::$delimiter, $parts);
        }

        return $parts;
    }
}
