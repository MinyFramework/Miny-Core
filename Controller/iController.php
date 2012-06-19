<?php

namespace Miny\Controller;

interface iController {

    public function run($controller, $action, array $params = NULL);
}