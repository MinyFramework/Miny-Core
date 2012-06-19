<?php

/**
 * This file is part of the Miny framework.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version accepted by the author in accordance with section
 * 14 of the GNU General Public License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   Miny
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny;

use \Miny\Controller\iControllerResolver;
use \Miny\Event\Event;
use \Miny\Event\EventDispatcher;
use \Miny\HTTP\Response;
use \Miny\HTTP\Request;

class Dispatcher {

    private $events;
    private $controller_resolver;

    public function __construct(EventDispatcher $events, iControllerResolver $resolver) {
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