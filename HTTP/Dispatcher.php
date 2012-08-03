<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\HTTP;

use Exception;
use InvalidArgumentException;
use Miny\Controller\ControllerResolver;
use Miny\Event\Event;
use Miny\Event\EventDispatcher;

class Dispatcher
{
    private $events;
    private $controller_resolver;

    public function __construct(EventDispatcher $evts, ControllerResolver $resolver)
    {
        $this->events = $evts;
        $this->controller_resolver = $resolver;
    }

    private function getResponse(Request $r)
    {
        $action = isset($r->get['action']) ? $r->get['action'] : NULL;
        $resolver = $this->controller_resolver;
        return $resolver->resolve($r->get['controller'], $action, $r);
    }

    private function handle(Request $r)
    {
        try {
            $event = new Event('filter_request', array('request' => $r));
            $this->events->raiseEvent($event);
        } catch (Exception $e) {
            $event = new Event('handle_request_exception', array(
                        'request'   => $r,
                        'exception' => $e
                    ));
            $this->events->raiseEvent($event);
            if ($event->isHandled()) {
                //Let's retry with the fallback-request
                $event = new Event('filter_request', array('request' => $r));
                $this->events->raiseEvent($event);
            }
        }
        if ($event->hasResponse()) {
            $rsp = $event->getResponse();
            if ($rsp instanceof Response) {
                return $rsp;
            }
        }
        return $this->getResponse($r);
    }

    private function getEventResponse(Event $event)
    {
        if (!$event->isHandled()) {
            throw new InvalidArgumentException('Event was not handled: ' . $event);
        }
        return $event->getResponse();
    }

    private function handleException(Exception $e)
    {
        $event = new Event('handle_exception', array('exception' => $e));
        $this->events->raiseEvent($event);
        if ($event->hasResponse()) {
            return $this->getEventResponse($event);
        } else {
            throw $e;
        }
    }

    public function dispatch(Request $r)
    {
        try {
            $rsp = $this->handle($r);
        } catch (Exception $e) {
            $rsp = $this->handleException($e);
        }

        if (!$rsp instanceof Response) {
            $event = new Event('invalid_response', array('response' => $rsp));
            $this->events->raiseEvent($event);
            $rsp = $this->getEventResponse($event);
        }

        $event = new Event('filter_response',
                        array(
                            'response' => $rsp,
                            'request'  => $r
                        )
        );
        $this->events->raiseEvent($event);
        if ($event->hasResponse()) {
            $rsp = $event->getResponse();
        }
        return $rsp;
    }

}