<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Controller;

use Miny\Event\EventDispatcher;
use Miny\HTTP\Request;
use Miny\HTTP\Response;

abstract class AbstractControllerRunner
{
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param EventDispatcher $eventDispatcher
     */
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
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public final function run(Request $request, Response $response)
    {
        $this->initController($request, $response);
        $event = $this->eventDispatcher->raiseEvent(
            $this->createLoadedEvent()
        );

        if ($event->isHandled() && $event->getResponse() instanceof Response) {
            return $event->getResponse();
        }

        $this->eventDispatcher->raiseEvent(
            $this->createFinishedEvent(
                $this->runController($request, $response)
            )
        );

        return $response;
    }

    abstract protected function runController(Request $request, Response $response);

    protected abstract function createLoadedEvent();

    protected abstract function createFinishedEvent($retVal);

    protected function initController(Request $request, Response $response)
    {
    }
}
