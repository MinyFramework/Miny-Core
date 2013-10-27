<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Miny\View;

use OutOfBoundsException;

class ViewBase
{
    protected $variables = array();

    public function __set($key, $value)
    {
        $this->variables[$key] = $value;
    }

    public function __get($key)
    {
        if (!$this->__isset($key)) {
            throw new OutOfBoundsException('Key not set: ' . $key);
        }
        return $this->variables[$key];
    }

    public function __isset($key)
    {
        return isset($this->variables[$key]);
    }

    public function __unset($key)
    {
        unset($this->variables[$key]);
    }

}
