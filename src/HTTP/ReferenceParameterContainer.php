<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\HTTP;

use OutOfBoundsException;

class ReferenceParameterContainer extends ParameterContainer
{
    public function __construct(array &$data)
    {
        $this->data = & $data;
    }

    public function &get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->data[$key];
        }
        if ($default === null) {
            throw new OutOfBoundsException(sprintf('Key %s is not set.', $key));
        }

        return $default;
    }
}
