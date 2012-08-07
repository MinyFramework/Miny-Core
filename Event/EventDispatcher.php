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
        } else {
            $count = count($this->handlers[$event]);
            if ($place === NULL || $place > $count) {
                $this->handlers[$event][] = array($handler, $method);
            } else {
                for ($i = $count; $i > $place; --$i) {
                    $this->handlers[$event][$i] = $this->handlers[$event][$i - 1];
                }
                $this->handlers[$event][$place] = array($handler, $method);
            }
        }
    }

    public function raiseEvent(Event $event)
    {
        $name = $event->getName();
        if (!isset($this->handlers[$name])) {
            return;
        }
        foreach ($this->handlers[$name] as $handler) {
            list($evt_handler, $method) = $handler;
            if (method_exists($evt_handler, $method)) {
                $evt_handler->$method($event);
            } else {
                $evt_handler->handle($event);
            }
        }
        $event->setHandled();
    }

}