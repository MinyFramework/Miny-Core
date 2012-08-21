<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Event;

class Event
{
    private $parameters;
    private $name;
    private $response;
    private $is_handled = false;

    public function __construct($name)
    {
        $this->name = $name;

        $parameters = func_get_args();
        array_shift($parameters);

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