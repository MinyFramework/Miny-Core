<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Controller\Runners;

use Miny\Controller\AbstractControllerRunner;
use Miny\Controller\Controller;
use Miny\Controller\Events\ControllerFinishedEvent;
use Miny\Controller\Events\ControllerLoadedEvent;
use Miny\Event\EventDispatcher;
use Miny\Factory\Container;
use Miny\HTTP\Request;
use Miny\HTTP\Response;

class StringControllerRunner extends AbstractControllerRunner
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var string
     */
    private $controllerPattern = '\\Application\\Controllers\\%sController';

    /**
     * @var Controller
     */
    private $controller;

    /**
     * @var string
     */
    private $action;

    public function __construct(Container $container, EventDispatcher $eventDispatcher)
    {
        $this->container = $container;
        parent::__construct($eventDispatcher);
    }

    /**
     * @param string $controllerPattern
     */
    public function setControllerPattern($controllerPattern)
    {
        $this->controllerPattern = $controllerPattern;
    }

    /**
     * @inheritdoc
     */
    public function canRun(Request $request)
    {
        $class = $request->get('controller');
        if (!is_string($class)) {
            return false;
        }
        if (!class_exists($class)) {
            // Try to guess the controller class if only a name is given
            $class = sprintf($this->controllerPattern, ucfirst($class));

            if (!class_exists($class)) {
                return false;
            }
        }
        $controller = $this->container->get($class);
        if (!$controller instanceof Controller) {
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
        $this->controller->setLogger(
            $this->container->get('\\Miny\\Log\\Log')
        );
        $this->controller->setRouteGenerator(
            $this->container->get('\\Miny\\Router\\RouteGenerator')
        );
        $this->controller->setParameterContainer(
            $this->container->get('\\Miny\\Factory\\ParameterContainer')
        );

        $this->action = $request->get('action', $this->controller->getDefaultAction());
    }

    protected function runController(Request $request, Response $response)
    {
        /** @var $controller Controller */
        return $this->controller->run($this->action, $request, $response);
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
