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

class ControllerLoadedEvent extends Event
{

    public function __construct($controller, $action)
    {
        parent::__construct(CoreEvents::CONTROLLER_LOADED, $controller, $action);
    }

}
