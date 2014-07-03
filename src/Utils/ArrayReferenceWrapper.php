<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Utils;

/**
 * Class ArrayReferenceWrapper acts as a thin wrapper around arrays to simulate pass-by-reference
 * behaviour.
 *
 * @author  Dániel Buga <bugadani@gmail.com>
 */
class ArrayReferenceWrapper implements \ArrayAccess
{
    private $data;

    public function __construct(array &$data)
    {
        $this->data = & $data;
    }

    public function add(array $data)
    {
        $this->data = $data + $this->data;
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    /**
     * @inheritdoc
     */
    public function &offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}
