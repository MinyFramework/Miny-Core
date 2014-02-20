<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Controller\Runners;

use Closure;
use Miny\Controller\AbstractControllerRunner;
use Miny\HTTP\Request;
use Miny\HTTP\Response;

class ClosureControllerRunner extends AbstractControllerRunner
{
    public function canRun($controller)
    {
        return $controller instanceof Closure;
    }

    protected function loadController($controller)
    {
        return $controller;
    }

    protected function runController(
        $controller,
        $action,
        Request $request,
        Response $response
    ) {
        return $controller($request, $action, $response);
    }
}
