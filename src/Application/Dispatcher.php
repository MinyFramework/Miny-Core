<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

use Miny\Factory\Factory;
use Miny\HTTP\Request;
use Miny\HTTP\Response;

class Dispatcher
{
    /**
     * @var Factory
     */
    private $factory;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function dispatch(Request $request)
    {
        $events = $this->factory->get('events');

        $old_request = $this->factory->replace('request', $request);
        $event       = $events->raiseEvent('filter_request', $request);

        $filter = true;
        if ($event->hasResponse()) {
            $rsp = $event->getResponse();
            if ($rsp instanceof Response) {
                $response = $rsp;
            } elseif ($rsp instanceof Request && $rsp !== $request) {
                $response = $this->dispatch($rsp);
                $filter   = false;
            }
        }

        ob_start();
        if (!isset($response)) {
            $oldResponse          = $this->factory->replace('response');
            $newResponse          = $this->factory->get('response');
            $controllerCollection = $this->factory->get('controllers');
            $controller           = $request->get['controller'];

            $controller_response = $controllerCollection->resolve($controller, $request, $newResponse);
            if ($oldResponse) {
                $response = $this->factory->replace('response', $oldResponse);
            } else {
                $response = $controller_response;
            }
        }

        if ($filter) {
            $events->raiseEvent('filter_response', $request, $response);
        }
        $response->addContent(ob_get_clean());

        if ($old_request) {
            $this->factory->replace('request', $old_request);
        }

        return $response;
    }
}
