<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Controller\Runners;

use Miny\Controller\AbstractControllerRunner;
use Miny\Controller\Events\ControllerFinishedEvent;
use Miny\Controller\Events\ControllerLoadedEvent;
use Miny\HTTP\Request;
use Miny\HTTP\Response;

class ClosureControllerRunner extends AbstractControllerRunner
{
    /**
     * @var \Closure
     */
    private $controller;

    /**
     * @var string
     */
    private $action;

    /**
     * @inheritdoc
     */
    public function canRun($controller)
    {
        if (!$controller instanceof \Closure) {
            return false;
        }
        $this->controller = $controller;

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function initController(Request $request, Response $response)
    {
        $this->action = $request->get()->get('action', '');
    }

    protected function runController(Request $request, Response $response)
    {
        $controller = $this->controller;

        return $controller($this->action, $request, $response);
    }

    protected function createLoadedEvent()
    {
        return new ControllerLoadedEvent($this->controller, $this->action);
    }

    protected function createFinishedEvent($retVal)
    {
        return new ControllerFinishedEvent($this->controller, $this->action, $retVal);
    }
}
