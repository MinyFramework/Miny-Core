<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\HTTP;

use InvalidArgumentException;
use OutOfBoundsException;

class ParameterContainer
{
    /**
     * @var array
     */
    protected $data;

    public function __construct(array $data = array())
    {
        $this->data = $data;
    }

    public function add(array $data)
    {
        $this->data = $data + $this->data;
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
            throw new OutOfBoundsException("Key {$key} is not set.");
        }

        return $default;
    }

    public function set($key, $value)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException('$key must be a string.');
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
