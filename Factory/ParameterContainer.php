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
        $this->addParameters($params);
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
     * Note: this function returns unresolved parameters by reference. Modifying the returned array may lead to
     * unexpected results.
     *
     * @return array
     */
    public function &toArray()
    {
        return $this->parameters;
    }

    /**
     * Notifies the container that the parameters array has changed
     * and it is necessary to refresh the resolved values.
     */
    public function notify()
    {
        unset($this->resolved_parameters);
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
                $k          = $this->resolveLinks($k);
                $return[$k] = $this->resolveLinks($v);
            }
            return $return;
        }
        if (is_string($value)) {
            $container = $this;
            $callback  = function ($matches) use ($container) {
                try {
                    $return = ArrayUtils::findByPath($container->toArray(), $matches[1]);
                    return $container->resolveLinks($return);
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
     * Note: This method returns a reference to the resolved value. Modifying the result will not modify the
     * unresolved parameters and will disappear when offsetSet, offsetUnset or addParameters is called.
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
