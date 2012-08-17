<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Event;

class EventDispatcher
{
    private $handlers = array();

    public function setHandler($event, EventHandler $handler, $method = NULL, $place = NULL)
    {
        if (!isset($this->handlers[$event])) {
            $this->handlers[$event] = array(array($handler, $method));
        } elseif ($place === NULL) {
            $this->handlers[$event][] = array($handler, $method);
        } else {
            array_splice($this->handlers[$event], $place, 0, array(array($handler, $method)));
        }
    }

    public function raiseEvent(Event $event)
    {
        $name = $event->getName();
        if (!isset($this->handlers[$name])) {
            return;
        }
        foreach ($this->handlers[$name] as $handler) {
            if (!$handler[1]) {
                $handler[1] = 'handle';
            }
            if (!is_callable($handler)) {
                throw new \UnexpectedValueException('Not callable handler set for event ' . $name);
            }
            call_user_func($handler, $event);
            if ($event->hasResponse()) {
                break;
            }
        }
        $event->setHandled();
    }

}