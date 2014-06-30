<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\HTTP;

class ParameterContainer
{
    /**
     * @var array|\ArrayAccess
     */
    protected $data;

    public function __construct($data = array())
    {
        if (!is_array($data) && !$data instanceof \ArrayAccess) {
            throw new \InvalidArgumentException('ParameterContainer::__construct() requires an array or array-like object.');
        }
        $this->data = $data;
    }

    public function add(array $data)
    {
        if (is_array($this->data)) {
            $this->data = $data + $this->data;
        } elseif (method_exists($this->data, 'add')) {
            $this->data->add($data);
        } else {
            foreach ($data as $key => $value) {
                $this->data[$key] = $value;
            }
        }
    }

    public function has($key)
    {
        return isset($this->data[$key]);
    }

    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->data[$key];
        }

        if ($default === null) {
            throw new \OutOfBoundsException("Key {$key} is not set.");
        }

        return $default;
    }

    public function set($key, $value)
    {
        if (!is_string($key)) {
            throw new \InvalidArgumentException('$key must be a string.');
        }

        $this->data[$key] = $value;
    }

    public function remove($key)
    {
        unset($this->data[$key]);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }
}
