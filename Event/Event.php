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
    /**
     * @var array
     */
    private $parameters;

    /**
     * @var string
     */
    private $name;

    /**
     * @var mixed
     */
    private $response;

    /**
     * @var bool
     */
    private $is_handled = false;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;

        $parameters = func_get_args();
        array_shift($parameters);

        $this->parameters = $parameters;
    }

    /**
     * @return boolean
     */
    public function isHandled()
    {
        return $this->is_handled;
    }

    public function setHandled()
    {
        $this->is_handled = true;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param mixed $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return boolean
     */
    public function hasResponse()
    {
        return $this->response !== NULL;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

}
