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
use UnexpectedValueException;

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
     * @param Container            $container
     * @param EventDispatcher      $events
     * @param ControllerCollection $controllers
     */
    public function __construct(
        Container $container,
        EventDispatcher $events,
        ControllerCollection $controllers
    ) {
        $this->container            = $container;
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
        $event      = $this->events->raiseEvent(CoreEvents::FILTER_REQUEST, $request);

        ob_start();
        if ($event->hasResponse()) {
            $rsp = $event->getResponse();
            if ($rsp instanceof Response) {
                $response = $rsp;
                $this->events->raiseEvent(CoreEvents::FILTER_RESPONSE, $request, $response);
            } elseif ($rsp instanceof Request) {
                $this->guardAgainstInfiniteRedirection($request, $rsp);
                $response = $this->dispatch($rsp);
            }
        }

        if (!isset($response)) {
            $response = $this->runController($request);
            $this->events->raiseEvent(CoreEvents::FILTER_RESPONSE, $request, $response);
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

    /**
     * @param Request $request
     * @param Request $rsp
     *
     * @throws UnexpectedValueException
     */
    protected function guardAgainstInfiniteRedirection(Request $request, Request $rsp)
    {
        if ($rsp === $request) {
            $message = 'This redirection would lead to an infinite loop.';
            throw new UnexpectedValueException($message);
        }
    }
}
