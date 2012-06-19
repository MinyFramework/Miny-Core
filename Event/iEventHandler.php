<?php

namespace Miny\Event;

interface iEventHandler {
        
    public function handle(\Miny\Event\Event $event, $handling_method = NULL);
    
}