<?php

namespace Miny\Event;

class Event implements iEvent {

    private $parameters;
    private $name;
    private $response;

    public function __construct($name, array $parameters = array()) {
        $this->name = $name;
        $this->parameters = $parameters;
    }

    public function getName() {
        return $this->name;
    }

    public function hasParameter($key) {
        return array_key_exists($key, $this->parameters);
    }

    public function getParameter($key) {
        if (!$this->hasParameter($key)) {
            throw new InvalidArgumentException('Event has no parameter "%s" set.', $key);
        }
        return $this->parameters[$key];
    }

    public function getParameters() {
        return $this->parameters;
    }

    public function setResponse($response) {
        $this->response = $response;
    }

    public function hasResponse() {
        return $this->response !== NULL;
    }

    public function getResponse() {
        return $this->response;
    }

}