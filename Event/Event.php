<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Event;

use OutOfBoundsException;

class Event
{
    private $parameters;
    private $name;
    private $response;
    private $is_handled = false;

    public function __construct($name, array $parameters = array())
    {
        $this->name = $name;
        $this->parameters = $parameters;
    }

    public function isHandled()
    {
        return $this->is_handled;
    }

    public function setHandled()
    {
        $this->is_handled = true;
    }

    public function getName()
    {
        return $this->name;
    }

    public function __toString()
    {
        return $this->name;
    }

    public function hasParameter($key)
    {
        return array_key_exists($key, $this->parameters);
    }

    public function getParameter($key)
    {
        if (!$this->hasParameter($key)) {
            throw new OutOfBoundsException('Event parameter not set: ' . $key);
        }
        return $this->parameters[$key];
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function setResponse($response)
    {
        $this->response = $response;
    }

    public function hasResponse()
    {
        return $this->response !== NULL;
    }

    public function getResponse()
    {
        return $this->response;
    }

}