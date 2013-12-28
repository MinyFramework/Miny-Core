<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Factory;

use ArrayAccess;
use OutOfBoundsException;

class ParameterContainer implements ArrayAccess
{
    /**
     * @var array
     */
    protected $parameters = array();

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
    public static function &find(array &$array, array $parts, $create = false)
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

    public function __construct(array $params = array())
    {
        $this->addParameters($params);
    }

    /**
     * @param array $array1
     * @param array $array2
     * @return array
     */
    private function merge(array $array1, array $array2, $overwrite = true)
    {
        foreach ($array2 as $key => $value) {
            if (isset($array1[$key])) {
                if (is_array($array1[$key]) && is_array($value)) {
                    $array1[$key] = $this->merge($array1[$key], $value, $overwrite);
                } elseif ($overwrite) {
                    $array1[$key] = $value;
                }
            } else {
                $array1[$key] = $value;
            }
        }
        return $array1;
    }

    /**
     * Stores an array of parameters.
     * Parameters are defined as a key-value pair (name => value)
     *
     * @param array $parameters
     */
    public function addParameters(array $parameters, $overwrite = true)
    {
        $this->parameters = $this->merge($this->parameters, $parameters, $overwrite);
    }

    /**
     * Retrieves all stored parameters.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->parameters;
    }

    /**
     * Retrieves all stored parameters with their links resolved.
     *
     * @return array
     */
    public function getResolvedParameters()
    {
        return $this->resolveLinks($this->parameters);
    }

    /**
     * @param string $value
     * @return string
     */
    public function resolveLinks($value)
    {
        if (is_array($value)) {
            $return = array();
            foreach ($value as $k => $v) {
                $k          = $this->resolveLinks($k);
                $return[$k] = $this->resolveLinks($v);
            }
            return $return;
        }
        if (is_string($value)) {
            $container = $this;
            $callback  = function ($matches) use ($container) {
                try {
                    return $container->offsetGet($matches[1]);
                } catch (OutOfBoundsException $e) {
                    return $matches[0];
                }
            };
            return preg_replace_callback('/(?<!\\\){@(.*?)}/', $callback, $value);
        }
        return $value;
    }

    /**
     * Processes a parameter string, which specifies a stored parameter.
     * You can use colons (:) to reference an element of an array within an array ...
     *
     * @param string $key The parameter to get.
     * @return mixed The parameter value
     * @throws OutOfBoundsException
     */
    public function offsetGet($key)
    {
        $return = self::find($this->parameters, explode(':', $key));
        return $this->resolveLinks($return);
    }

    /**
     * Stores a parameter for $key serving as key.
     *
     * @param string $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        if (strpos($key, ':') !== false) {
            $parts          = explode(':', $key);
            $last_key       = array_pop($parts);
            $arr            = & self::find($this->parameters, $parts);
            $arr[$last_key] = $value;
        } else {
            $this->parameters[$key] = $value;
        }
    }

    /**
     * Removes the parameter specified with $key.
     *
     * @param string $key
     */
    public function offsetUnset($key)
    {
        if (strpos($key, ':') !== false) {
            $parts    = explode(':', $key);
            $last_key = array_pop($parts);
            try {
                $arr = & self::find($this->parameters, $parts);
                unset($arr[$last_key]);
            } catch (OutOfBoundsException $e) {

            }
        } else {
            unset($this->parameters[$key]);
        }
    }

    /**
     * Indicates whether the parameter specified with $key is set.
     *
     * @param string $key
     * @return boolean
     */
    public function offsetExists($key)
    {
        if (strpos($key, ':') !== false) {
            $parts = explode(':', $key);
            try {
                self::find($this->parameters, $parts);
                return true;
            } catch (OutOfBoundsException $e) {
                return false;
            }
        } else {
            return isset($this->parameters[$key]);
        }
    }
}
