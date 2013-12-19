<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Event;

use InvalidArgumentException;
use Miny\Event\Exceptions\EventHandlerException;

class EventDispatcher
{
    /**
     * @var callback[]
     */
    private $handlers = array();

    /**
     * @param string $event
     * @param callback $handler
     * @param int $place
     * @throws EventHandlerException
     */
    public function register($event, $handler, $place = null)
    {
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

    public function raiseEvent()
    {
        $args  = func_get_args();
        $event = array_shift($args);

        if (is_string($event)) {
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
