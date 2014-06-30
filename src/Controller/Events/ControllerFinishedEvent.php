<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Controller\Events;

use Miny\CoreEvents;
use Miny\Event\Event;

class ControllerFinishedEvent extends Event
{
    private $controller;
    private $action;
    private $returnValue;

    public function __construct($controller, $action, $returnValue)
    {
        parent::__construct(CoreEvents::CONTROLLER_FINISHED);
        $this->controller  = $controller;
        $this->action      = $action;
        $this->returnValue = $returnValue;
    }

    public function getController()
    {
        return $this->controller;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getReturnValue()
    {
        return $this->returnValue;
    }

}
