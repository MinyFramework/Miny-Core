<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Event;

use BadMethodCallException;

abstract class EventHandler
{
    public function handle(Event $event)
    {
        throw new BadMethodCallException('Handler not implemented.');
    }

}