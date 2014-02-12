<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Factory;

class SubTreeWrapper extends AbstractConfigurationTree
{
    private $parameterContainer;
    private $root;

    public function __construct(ParameterContainer $parameterContainer, $root)
    {
        $this->parameterContainer = $parameterContainer;
        if (strpos($root, ':') !== false) {
            $root = explode(':', $root);
        }
        $this->root = $root;
    }

    public function offsetExists($offset)
    {
        $this->parameterContainer->offsetExists($this->modifyOffset($offset));
    }

    public function offsetGet($offset)
    {
        $this->parameterContainer->offsetGet($this->modifyOffset($offset));
    }

    public function offsetSet($offset, $value)
    {
        $this->parameterContainer->offsetSet($this->modifyOffset($offset), $value);
    }

    public function offsetUnset($offset)
    {
        $this->parameterContainer->offsetUnset($this->modifyOffset($offset));
    }

    private function modifyOffset($offset)
    {
        if (!is_array($offset)) {
            $offset = explode(':', $offset);
        }

        return $this->root + $offset;
    }

    public function getSubTree($root)
    {
        if (!is_array($root)) {
            $root = array($root);
        }

        return new SubTreeWrapper($this->parameterContainer, $this->root + $root);
    }
}
