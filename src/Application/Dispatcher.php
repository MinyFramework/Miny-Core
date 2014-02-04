<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

use Miny\Controller\ControllerCollection;
use Miny\Event\EventDispatcher;
use Miny\Factory\Container;
use Miny\HTTP\Request;
use Miny\HTTP\Response;

class Dispatcher
{
    /**
     * @var ControllerCollection
     */
    private $controllerCollection;

    /**
     * @var Container
     */
    private $factory;

    /**
     * @var EventDispatcher
     */
    private $events;

    /**
     * @param Container            $factory
     * @param EventDispatcher      $events
     * @param ControllerCollection $controllers
     */
    public function __construct(Container $factory, EventDispatcher $events, ControllerCollection $controllers)
    {
        $this->factory              = $factory;
        $this->events               = $events;
        $this->controllerCollection = $controllers;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function dispatch(Request $request)
    {
        $oldRequest = $this->factory->setInstance($request);
        $event      = $this->events->raiseEvent('filter_request', $request);

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
            $newResponse = $this->factory->get('\Miny\HTTP\Response', array(), true);
            $oldResponse = $this->factory->setInstance($newResponse);

            $controller = $request->get['controller'];

            $controller_response = $this->controllerCollection->resolve($controller, $request, $newResponse);
            if ($oldResponse) {
                $response = $this->factory->setInstance($oldResponse);
            } else {
                $response = $controller_response;
            }
        }

        if ($filter) {
            $this->events->raiseEvent('filter_response', $request, $response);
        }
        $response->addContent(ob_get_clean());

        if ($oldRequest) {
            $this->factory->setInstance($oldRequest);
        }

        return $response;
    }
}
