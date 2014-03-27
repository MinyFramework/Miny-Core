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
use Miny\HTTP\Request;

class FilterRequestEvent extends Event
{

    public function __construct(Request $request)
    {
        parent::__construct(CoreEvents::FILTER_REQUEST, $request);
    }

}
