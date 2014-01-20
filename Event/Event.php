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

    /**
     * @param string $name The event name.
     * @param mixed ... Event parameters.
     */
    public function __construct($name)
    {
        $this->name = $name;
        $parameters = array_slice(func_get_args(), 1);

        if (isset($parameters[0]) && is_array($parameters[0]) && count($parameters) == 1) {
            $this->parameters = $parameters[0];
        } else {
            $this->parameters = $parameters;
        }
    }

    /**
     * @return bool Whether or not the event was handled.
     */
    public function isHandled()
    {
        return $this->is_handled;
    }

    /**
     * Sets the event as 'handled'.
     */
    public function setHandled()
    {
        $this->is_handled = true;
    }

    /**
     * @return string The name of the event.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array The parameters of the event.
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Sets a response for the event. This is used to retrieve information from the handlers.
     *
     * @param mixed $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return boolean Whether the event has a response.
     */
    public function hasResponse()
    {
        return $this->response !== null;
    }

    /**
     * @return mixed The response for the event.
     */
    public function getResponse()
    {
        return $this->response;
    }
}
