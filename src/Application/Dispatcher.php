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
    private $container;

    /**
     * @var EventDispatcher
     */
    private $events;

    /**
     * @param Container            $factory
     * @param EventDispatcher      $events
     * @param ControllerCollection $controllers
     */
    public function __construct(
        Container $factory,
        EventDispatcher $events,
        ControllerCollection $controllers
    ) {
        $this->container              = $factory;
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
        $oldRequest = $this->container->setInstance($request);
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
            $response = $this->runController($request);
        }

        if ($filter) {
            $this->events->raiseEvent('filter_response', $request, $response);
        }
        $response->addContent(ob_get_clean());

        if ($oldRequest) {
            $this->container->setInstance($oldRequest);
        }

        return $response;
    }

    /**
     * @param Request $request
     *
     * @return false|Response|object
     */
    protected function runController(Request $request)
    {
        /** @var $newResponse Response */
        $newResponse = $this->container->get(
            '\\Miny\\HTTP\\Response',
            array(),
            true
        );
        $oldResponse = $this->container->setInstance($newResponse);

        $controller = $request->get['controller'];

        $controllerResponse = $this->controllerCollection->resolve(
            $controller,
            $request,
            $newResponse
        );

        if ($oldResponse) {
            return $this->container->setInstance($oldResponse);
        } else {
            return $controllerResponse;
        }
    }
}
