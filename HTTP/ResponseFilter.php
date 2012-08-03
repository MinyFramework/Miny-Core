<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\HTTP;

use Miny\Event\Event;
use Miny\Event\EventHandler;
use Miny\HTTP\Response;

class ResponseFilter extends EventHandler
{
    public function filterStringToResponse(Event $event)
    {
        $response = $event->getParameter('response');
        if (!$response instanceof Response) {
            $event->setResponse(new Response($response));
        }
    }

}