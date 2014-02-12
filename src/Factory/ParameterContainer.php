<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Factory;

use Miny\Utils\ArrayUtils;
use OutOfBoundsException;

class ParameterContainer extends AbstractConfigurationTree
{
    /**
     * @var array
     */
    protected $parameters = array();
    private $links;

    public function __construct(array $params = array())
    {
        $this->parameters = $params;
        $this->links      = array();
    }

    /**
     * Stores an array of parameters.
     * Parameters are defined as a key-value pair (name => value)
     *
     * @param array $parameters
     * @param bool  $overwrite
     */
    public function addParameters(array $parameters, $overwrite = true)
    {
        $this->parameters = ArrayUtils::merge($this->parameters, $parameters, $overwrite);
        $this->links      = array();
    }

    /**
     * Retrieves all stored parameters.
     *
     * @return array
     */
    public function &toArray()
    {
        return $this->parameters;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function resolveLinks($value)
    {
        if (is_array($value)) {
            $return = array();
            foreach ($value as $k => $v) {
                $return[$k] = $this->resolveLinks($v);
            }

            return $return;
        }

        if (is_string($value) && strpos($value, '{@') !== false) {
            $value = $this->resolveLinksInString($value);
        }

        return $value;
    }

    /**
     * @param string $string
     *
     * @return string mixed
     */
    public function resolveLinksInString($string)
    {
        $container = $this;

        return preg_replace_callback(
            '/(?<!\\\){@(.*?)}/',
            function ($matches) use ($container) {
                try {
                    return $container->offsetGet($matches[1]);
                } catch (OutOfBoundsException $e) {
                    return $matches[0];
                }
            },
            $string
        );
    }

    public function getSubTree($root)
    {
        return new SubTreeWrapper($this, $root);
    }

    /**
     * Processes a parameter string, which specifies a stored parameter.
     * You can use colons (:) to reference an element of an array within an array ...
     *
     * @param mixed $key The parameter to get.
     *
     * @return mixed The parameter value
     *
     * @throws OutOfBoundsException
     */
    public function offsetGet($key)
    {
        $arr_key = ArrayUtils::implodeIfArray($key, ':');
        if (!isset($this->links[$arr_key])) {
            $val                   = ArrayUtils::findByPath($this->parameters, $key);
            $this->links[$arr_key] = $this->resolveLinks($val);
        }

        return $this->links[$arr_key];
    }

    /**
     * Stores a parameter for $key serving as key.
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        ArrayUtils::setByPath($this->parameters, $key, $value);
        $key = ArrayUtils::implodeIfArray($key, ':');
        if (isset($this->links[$key])) {
            $this->links[$key] = $value;
        }
    }

    /**
     * Removes the parameter specified with $key.
     *
     * @param mixed $key
     */
    public function offsetUnset($key)
    {
        ArrayUtils::unsetByPath($this->parameters, $key);
        $key = ArrayUtils::implodeIfArray($key, ':');
        if (isset($this->links[$key])) {
            unset($this->links[$key]);
        }
    }

    /**
     * Indicates whether the parameter specified with $key is set.
     *
     * @param string $key
     *
     * @return boolean
     */
    public function offsetExists($key)
    {
        return ArrayUtils::existsByPath($this->parameters, $key);
    }
}
