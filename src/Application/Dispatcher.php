<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

use Miny\Application\Events\FilterRequestEvent;
use Miny\Application\Events\FilterResponseEvent;
use Miny\Controller\ControllerDispatcher;
use Miny\Event\EventDispatcher;
use Miny\Factory\Container;
use Miny\HTTP\Request;
use Miny\HTTP\Response;
use UnexpectedValueException;

class Dispatcher
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var EventDispatcher
     */
    private $events;

    /**
     * @var ControllerDispatcher
     */
    private $controllerDispatcher;

    /**
     * @param Container            $container
     * @param EventDispatcher      $events
     * @param ControllerDispatcher $controllerDispatcher
     */
    public function __construct(
        Container $container,
        EventDispatcher $events,
        ControllerDispatcher $controllerDispatcher
    ) {
        $this->container            = $container;
        $this->events               = $events;
        $this->controllerDispatcher = $controllerDispatcher;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function dispatch(Request $request)
    {
        $oldRequest = $this->container->setInstance($request);
        $event      = $this->events->raiseEvent(new FilterRequestEvent($request));

        ob_start();
        if ($event->hasResponse()) {
            $rsp = $event->getResponse();
            if ($rsp instanceof Response) {
                $response = $rsp;
                $this->events->raiseEvent(new FilterResponseEvent($request, $response));
            } elseif ($rsp instanceof Request) {
                $this->guardAgainstInfiniteRedirection($request, $rsp);
                $response = $this->dispatch($rsp);
            }
        }

        if (!isset($response)) {
            $response = $this->controllerDispatcher->runController($request);
            $this->events->raiseEvent(new FilterResponseEvent($request, $response));
        }
        $response->addContent(ob_get_clean());

        if ($oldRequest) {
            $this->container->setInstance($oldRequest);
        }

        return $response;
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
            throw new UnexpectedValueException('This redirection would lead to an infinite loop.');
        }
    }
}
