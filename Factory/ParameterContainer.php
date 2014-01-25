<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Factory;

use ArrayAccess;
use Miny\Utils\ArrayUtils;
use OutOfBoundsException;

class ParameterContainer implements ArrayAccess
{
    /**
     * @var array
     */
    protected $parameters = array();

    /**
     * @var array
     */
    protected $resolved_parameters;

    public function __construct(array $params = array())
    {
        $this->parameters = $params;
    }

    /**
     * Stores an array of parameters.
     * Parameters are defined as a key-value pair (name => value)
     *
     * @param array $parameters
     * @param bool $overwrite
     */
    public function addParameters(array $parameters, $overwrite = true)
    {
        $this->parameters = ArrayUtils::merge($this->parameters, $parameters, $overwrite);
        unset($this->resolved_parameters);
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
     * Retrieves all stored parameters with their links resolved.
     *
     * @return array
     */
    public function getResolvedParameters()
    {
        if (!isset($this->resolved_parameters)) {
            $this->resolved_parameters = $this->resolveLinks($this->parameters);
        }
        return $this->resolved_parameters;
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
            // This cannot be done by calling offsetGet as links are not resolved yet.
            $container = $this;
            $callback  = function ($matches) use ($container) {
                $return = ArrayUtils::getByPath($container->toArray(), $matches[1], $matches[0]);
                if ($return !== $matches[0]) {
                    $return = $container->resolveLinks($return);
                }
                return $return;
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
     *
     * @return mixed The parameter value
     *
     * @throws OutOfBoundsException
     */
    public function &offsetGet($key)
    {
        $resolved = $this->getResolvedParameters();
        return ArrayUtils::findByPath($resolved, $key);
    }

    /**
     * Stores a parameter for $key serving as key.
     *
     * @param string $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        ArrayUtils::setByPath($this->parameters, $key, $value);
        unset($this->resolved_parameters);
    }

    /**
     * Removes the parameter specified with $key.
     *
     * @param string $key
     */
    public function offsetUnset($key)
    {
        ArrayUtils::unsetByPath($this->parameters, $key);
        unset($this->resolved_parameters);
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
