<?php

namespace Miny\Event;

interface iEvent {

    public function getName();

    public function hasParameter($key);

    public function getParameter($key);

    public function getParameters();

    public function setResponse($response);
}