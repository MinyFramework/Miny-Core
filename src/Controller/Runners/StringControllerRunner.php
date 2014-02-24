<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Controller\Runners;

use Miny\Controller\AbstractControllerRunner;
use Miny\Controller\Controller;
use Miny\Controller\Exceptions\InvalidControllerException;
use Miny\Controller\Exceptions\MissingControllerException;
use Miny\Event\EventDispatcher;
use Miny\Factory\Container;
use Miny\HTTP\Request;
use Miny\HTTP\Response;

class StringControllerRunner extends AbstractControllerRunner
{
    private $container;
    private $controllerPattern = '\\Application\\Controllers\\%sController';

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

    public function canRun($controller)
    {
        return is_string($controller);
    }

    protected function loadController($class)
    {
        if (!class_exists($class)) {
            $class = sprintf($this->controllerPattern, ucfirst($class));
        }
        if (!class_exists($class)) {
            $message = sprintf('Controller %s is not found', $class);
            throw new MissingControllerException($message);
        }

        $controller = $this->container->get($class);
        if (!$controller instanceof Controller) {
            $message = sprintf('Class %s is not a valid controller', $class);
            throw new InvalidControllerException($message);
        }

        return $controller;
    }

    protected function runController(
        $controller,
        $action,
        Request $request,
        Response $response
    ) {
        return $controller->run($action, $request, $response);
    }

    protected function getAction(Request $request, Controller $controller)
    {
        return $request->get()->get('action', $controller->getDefaultAction());
    }
}
