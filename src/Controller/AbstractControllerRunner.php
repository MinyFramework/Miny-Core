<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Controller;

use Miny\CoreEvents;
use Miny\Event\EventDispatcher;
use Miny\HTTP\Request;
use Miny\HTTP\Response;

abstract class AbstractControllerRunner
{
    private $eventDispatcher;

    public function __construct(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Determines if $controller is acceptable by the runner.
     *
     * @param $controller
     *
     * @return bool
     */
    abstract public function canRun($controller);

    /**
     * @param          $controller
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function run($controller, Request $request, Response $response)
    {
        $controller = $this->loadController($controller);
        $action     = $this->getAction($request, $controller);

        $event = $this->eventDispatcher->raiseEvent(
            CoreEvents::CONTROLLER_LOADED,
            $controller,
            $action
        );

        if ($event->isHandled() && $event->getResponse() instanceof Response) {
            return $event->getResponse();
        }

        $retVal = $this->runController($controller, $action, $request, $response);

        $this->eventDispatcher->raiseEvent(
            CoreEvents::CONTROLLER_FINISHED,
            $controller,
            $action,
            $retVal
        );

        return $response;
    }

    /**
     * Loads the controller if the runner requires it, e.g. getAction needs an object.
     *
     * @param $controller
     *
     * @return mixed
     */
    protected function loadController($controller)
    {
        return $controller;
    }

    /**
     * @param Request $request
     * @param         $controller
     *
     * @return string
     */
    protected function getAction(Request $request, $controller)
    {
        return $request->get()->get('action');
    }

    abstract protected function runController(
        $controller,
        $action,
        Request $request,
        Response $response
    );
}
