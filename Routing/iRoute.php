<?php

namespace Miny\Routing;

interface iRoute {
    
    public function match($path, $method = NULL);
    
    public function get($parameter = NULL);
    
    public function generate($name, array $parameters = array());
}