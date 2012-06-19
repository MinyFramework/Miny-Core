<?php

namespace Miny;

use \Miny\Event\Event;
use \Miny\HTTP\Response;
use \Miny\HTTP\Request;

class Dispatcher {

    private $events;
    private $controller_resolver;

    public function __construct(\Miny\Event\EventDispatcher $events, \Miny\Controller\iControllerResolver $resolver) {
        $this->events = $events;
        $this->controller_resolver = $resolver;
    }

    private function filterRequest(Request $r) {
        $event = new Event('filter_request', array('request' => $r));
        $this->events->raiseEvent($event);
        if ($event->hasResponse()) {
            $rsp = $event->getResponse();
            if ($rsp instanceof Response) {
                return $rsp;
            } else if ($rsp instanceof Request) {
                $r = $rsp;
            }
        }
    }

    private function getResponse(Request $r) {
        $get = $r->get();
        $params = $r->getHTTPParams();
        $action = isset($get['action']) ? $get['action'] : NULL;
        return $this->controller_resolver->resolve($get['controller'], $action, $params);
    }

    private function handle(Request $r) {
        $this->filterRequest($r);

        $rsp = $this->getResponse($r);

        if (!$rsp instanceof Response) {
            $event = new Event('invalid_response', array('response' => $rsp));
            $this->events->raiseEvent($event);
            $rsp = $this->getEventResponse($event);
        }
        return $rsp;
    }

    private function getEventResponse(Event $event) {
        $response = $event->getResponse();
        if (!$response instanceof Response) {
            throw new \RuntimeException(
                    sprintf('Invalid response, (%s) %s given.', gettype($response), $response));
        }
        return $response;
    }

    private function handleException(\Exception $e) {
        $event = new Event('handle_exception', array('exception' => $e));
        $this->events->raiseEvent($event);
        if ($event->hasResponse()) {
            return $this->getEventResponse($event);
        } else {
            throw $e;
        }
    }

    public function dispatch(Request $r) {
        try {
            $rsp = $this->handle($r);
        } catch (\Exception $e) {
            $rsp = $this->handleException($e);
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