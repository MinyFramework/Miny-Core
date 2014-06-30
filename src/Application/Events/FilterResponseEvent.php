<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application\Events;

use Miny\CoreEvents;
use Miny\Event\Event;
use Miny\HTTP\Request;
use Miny\HTTP\Response;

class FilterResponseEvent extends Event
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $httpResponse;

    public function __construct(Request $request, Response $response)
    {
        parent::__construct(CoreEvents::FILTER_RESPONSE);
        $this->request      = $request;
        $this->httpResponse = $response;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return Response
     */
    public function getHttpResponse()
    {
        return $this->httpResponse;
    }
}
