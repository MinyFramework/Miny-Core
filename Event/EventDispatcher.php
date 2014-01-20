<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Event;

use Closure;
use InvalidArgumentException;
use Miny\Event\Exceptions\EventHandlerException;

class EventDispatcher
{
    /**
     * @var (callable|object)[] The registered event handlers in the form of event => handler(s)
     */
    private $handlers = array();

    /**
     * Register an event handler for $event.
     * The event handler can be a callback or an object.
     * When the handler is an object, the event handler will look for a method with the name of the event.
     *
     * If $place is specified, $handler will be inserted as the $place-th handler in the queue.
     *
     * @param string $event
     * @param callable|object $handler
     * @param int $place
     *
     * @throws EventHandlerException if the handler is not callable.
     */
    public function register($event, $handler, $place = null)
    {
        if(is_object($handler)) {
            if(!$handler instanceof Closure) {
                $handler = array($handler, $event);
            }
        }
        if (!is_callable($handler)) {
            throw new EventHandlerException('Handler is not callable for event ' . $event);
        }
        if (!isset($this->handlers[$event])) {
            $this->handlers[$event] = array($handler);
        } elseif ($place === null) {
            $this->handlers[$event][] = $handler;
        } else {
            //insert handler to the given place
            array_splice($this->handlers[$event], $place, 0, array($handler));
        }
    }

    /**
     * Raises $event.
     * If $event is a string, raiseEvent will create an Event instance with $event as the event name and the optional
     * arguments as event parameters.
     *
     * @param string|Event $event
     * @param mixed ... Event parameters if $event is string.
     *
     * @return Event The raised event.
     *
     * @throws InvalidArgumentException
     */
    public function raiseEvent($event)
    {
        if (is_string($event)) {
            $args = array_slice(func_get_args(), 1);
            $event = new Event($event, $args);
        }
        if (!$event instanceof Event) {
            throw new InvalidArgumentException('The first parameter must be an Event object or a string.');
        }
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
