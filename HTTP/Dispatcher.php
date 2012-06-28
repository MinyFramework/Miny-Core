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
 * @package   Miny/HTTP
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\HTTP;

use \Miny\Controller\ControllerResolver;
use \Miny\Event\Event;
use \Miny\Event\EventDispatcher;

class Dispatcher
{
    private $events;
    private $controller_resolver;

    public function __construct(EventDispatcher $evts,
                                ControllerResolver $resolver)
    {
        $this->events = $evts;
        $this->controller_resolver = $resolver;
    }

    private function getResponse(Request $r)
    {
        $get = $r->get();
        $params = $r->getHTTPParams();
        $action = isset($get['action']) ? $get['action'] : NULL;
        $resolver = $this->controller_resolver;
        return $resolver->resolve($get['controller'], $action, $params);
    }

    private function handle(Request $r)
    {
        try {
            $event = new Event('filter_request', array('request' => $r));
            $this->events->raiseEvent($event);
        } catch (\Exception $e) {
            $event = new Event('handle_request_exception', array(
                        'request'   => $r,
                        'exception' => $e
                    ));
            $this->events->raiseEvent($event);
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
        $response = $event->getResponse();
        if (!$response instanceof Response) {
            $message = 'Invalid response, (%s) %s given.';
            $message = sprintf($message, gettype($response), $response);
            throw new \RuntimeException($message);
        }
        return $response;
    }

    private function handleException(\Exception $e)
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