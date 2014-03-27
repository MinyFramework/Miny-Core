<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application\Events;

use Miny\CoreEvents;
use Miny\Event\Event;

class ShutDownEvent extends Event
{

    public function __construct()
    {
        parent::__construct(CoreEvents::SHUTDOWN);
    }

}
