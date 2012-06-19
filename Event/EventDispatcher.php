<?php

namespace Miny\Event;

class EventDispatcher {

    private $handlers = array();

    public function setHandler($event, iEventHandler $handler, $method = 'handle') {
        $this->handlers[$event][] = array($handler, $method);
    }

    public function raiseEvent(iEvent $event) {
        $name = $event->getName();
        if (isset($this->handlers[$name])) {
            foreach ($this->handlers[$name] as $handler) {
                list($evt_handler, $method) = $handler;
                if (method_exists($evt_handler, $method)) {
                    $evt_handler->$method($event);
                } else {
                    $evt_handler->handle($event, $method);
                }
            }
            return true;
        }
        return false;
    }

}