<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Event;

use Miny\Event\Exceptions\EventHandlerException;

class EventDispatcher
{
    /**
     * @var array The registered event handlers in the form of event => array of handlers
     */
    private $handlers = [];

    /**
     * @param $handler
     * @param $event
     *
     * @return array
     * @throws Exceptions\EventHandlerException
     */
    private function ensureCallback($handler, $event)
    {
        if (!is_callable($handler)) {
            if (is_object($handler)) {
                if (!$handler instanceof \Closure) {
                    $handler = [$handler, $event];
                }
            }
            if (!is_callable($handler)) {
                throw new EventHandlerException("Handler is not callable for event {$event}");
            }
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
     * @param int             $priority
     *
     * @throws EventHandlerException if the handler is not callable.
     */
    public function register($event, $handler, $priority = 0)
    {
        if (!isset($this->handlers[ $event ])) {
            $this->handlers[ $event ] = [];
        }
        if (!isset($this->handlers[ $event ][ $priority ])) {
            $this->handlers[ $event ][ $priority ] = [];
        }
        $this->handlers[ $event ][ $priority ][] = $this->ensureCallback($handler, $event);
    }

    public function registerHandlers($event, $handlers)
    {
        if (!is_array($handlers) || is_callable($handlers)) {
            $this->register($event, $handlers);
        } else {
            foreach ($handlers as $handler) {
                $this->register($event, $handler);
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
        if (isset($this->handlers[ $name ])) {
            ksort($this->handlers[ $name ]);
            $response = null;
            foreach ($this->handlers[ $name ] as $handlers) {
                foreach ($handlers as $handler) {
                    $response = $handler($event);
                }
            }
            $event->setResponse($response);
            $event->setHandled();
        }

        return $event;
    }
}
