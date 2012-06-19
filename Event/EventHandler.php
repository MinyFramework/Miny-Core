<?php

namespace Miny\Event;

class EventHandler implements iEventHandler {

    public function handle(\Miny\Event\Event $event, $handling_method = NULL) {
        throw new \BadMethodCallException('Handler not exists: ' . $handling_method);
    }

}