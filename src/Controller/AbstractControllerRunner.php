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

    abstract public function canRun($controller);

    /**
     * @param          $controller
     * @param Request  $request
     * @param Response $response
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
    }

    abstract protected function loadController($class);

    protected function getAction(Request $request, Controller $controller)
    {
        return $request->get('action');
    }

    abstract protected function runController(
        $controller,
        $action,
        Request $request,
        Response $response
    );
}