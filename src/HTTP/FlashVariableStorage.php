<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\HTTP;

class FlashVariableStorage
{
    private $storage;

    public function __construct(&$storage)
    {
        if (!(is_array($storage) || $storage instanceof \ArrayAccess)) {
            throw new \InvalidArgumentException('$storage must be an array or array-like object');
        }
        $this->storage = $storage;
    }

    public function decrement()
    {
        $this->storage = array_map(
            function ($flash) {
                //2: decrease flash ttl
                --$flash['ttl'];

                return $flash;
            },
            array_filter(
                $this->storage,
                function ($flash) {
                    //1: remove expired elements
                    return $flash['ttl'] > 0;
                }
            )
        );
    }

    public function has($key)
    {
        return isset($this->storage[ $key ]);
    }

    public function set($key, $data, $ttl = 1)
    {
        $this->storage[ $key ] = ['data' => $data, 'ttl' => (int)$ttl];
    }

    public function &get($key)
    {
        if (!isset($this->storage[ $key ])) {
            throw new \OutOfBoundsException("Flash key '{$key}' is not found");
        }

        return $this->storage[ $key ]['data'];
    }

    public function remove($key)
    {
        unset($this->storage[ $key ]);
    }
}