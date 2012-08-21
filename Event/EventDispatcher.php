<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Event;

use Miny\Event\Exceptions\EventHandlerException;

class EventDispatcher
{
    private $handlers = array();

    public function register($event, $handler, $place = NULL)
    {
        if (!is_callable($handler)) {
            throw new EventHandlerException('Handler is not callable for event ' . $event);
        }
        if (!isset($this->handlers[$event])) {
            $this->handlers[$event] = array($handler);
        } elseif ($place === NULL) {
            $this->handlers[$event][] = $handler;
        } else {
            array_splice($this->handlers[$event], $place, 0, array($handler));
        }
    }

    public function raiseEvent(Event $event)
    {
        $name = $event->getName();
        if (!isset($this->handlers[$name])) {
            return;
        }
        $parameters = $event->getParameters();
        array_unshift($parameters, $event);
        foreach ($this->handlers[$name] as $handler) {
            call_user_func_array($handler, $parameters);
            if ($event->hasResponse()) {
                break;
            }
        }
        $event->setHandled();
    }

}