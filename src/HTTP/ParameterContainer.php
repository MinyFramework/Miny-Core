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
    protected $data;

    public function __construct(array $data = array())
    {
        $this->data = $data;
    }

    public function has($key)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException('$key must be a string.');
        }

        return isset($this->data[$key]);
    }

    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->data[$key];
        }

        if ($default === null) {
            throw new OutOfBoundsException(sprintf('Key %s is not set.', $key));
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
        if (!is_string($key)) {
            throw new InvalidArgumentException('$key must be a string.');
        }

        unset($this->data[$key]);
    }
}
