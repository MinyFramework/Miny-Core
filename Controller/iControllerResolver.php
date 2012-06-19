<?php

namespace Miny\Controller;

interface iControllerResolver {

    public function resolve($controller, $action = NULL, array $params = array());
}