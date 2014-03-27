<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application\Events;

use Exception;
use Miny\CoreEvents;
use Miny\Event\Event;

class UncaughtExceptionEvent extends Event
{

    public function __construct(Exception $exception)
    {
        parent::__construct(CoreEvents::UNCAUGHT_EXCEPTION, $exception);
    }

}
