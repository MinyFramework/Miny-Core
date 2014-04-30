<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Event;

use Closure;
use Miny\Event\Exceptions\EventHandlerException;

class EventDispatcher
{
    /**
     * @var array The registered event handlers in the form of event => array of handlers
     */
    private $handlers = array();

    /**
     * @param $handler
     * @param $event
     *
     * @return array
     * @throws Exceptions\EventHandlerException
     */
    private function ensureCallback($handler, $event)
    {
        if (is_object($handler)) {
            if (!$handler instanceof Closure) {
                $handler = array($handler, $event);
            }
        }
        if (!is_callable($handler)) {
            throw new EventHandlerException("Handler is not callable for event {$event}");
        }

        return $handler;
    }

    /**
     * Register an event handler for $event.
     * The event handler can be a callback or an object.
     * When the handler is an object, the event handler will look for a method with the name of the event.
     *
     * If $place is specified, $handler will be inserted as the $place-th handler in the queue.
     *
     * @param string          $event
     * @param callable|object $handler
     * @param int             $place
     *
     * @throws EventHandlerException if the handler is not callable.
     */
    public function register($event, $handler, $place = null)
    {
        $handler = $this->ensureCallback($handler, $event);

        if (!isset($this->handlers[$event])) {
            $this->handlers[$event] = array($handler);
        } elseif ($place === null) {
            $this->handlers[$event][] = $handler;
        } else {
            //insert handler to the given place
            array_splice($this->handlers[$event], $place, 0, array($handler));
        }
    }

    public function registerHandlers($event, $handlers)
    {
        if (!isset($this->handlers[$event])) {
            $this->handlers[$event] = array();
        }
        if (!is_array($handlers) || is_callable($handlers)) {
            $this->handlers[$event][] = $this->ensureCallback($handlers, $event);
        } else {
            foreach ($handlers as $handler) {
                $this->handlers[$event][] = $this->ensureCallback($handler, $event);
            }
        }
    }

    /**
     * @param Event $event
     *
     * @return Event
     */
    public function raiseEvent(Event $event)
    {
        $name = $event->getName();
        if (isset($this->handlers[$name])) {
            $parameters = $event->getParameters();
            foreach ($this->handlers[$name] as $handler) {
                $response = call_user_func_array($handler, $parameters);
                if ($response !== null) {
                    $event->setResponse($response);
                    break;
                }
            }
            $event->setHandled();
        }

        return $event;
    }
}
