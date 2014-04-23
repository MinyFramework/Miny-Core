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
    /**
     * @var Container
     */
    private $container;

    /**
     * @var string
     */
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

    /**
     * @inheritdoc
     */
    public function canRun($controller)
    {
        return is_string($controller);
    }

    /**
     * @inheritdoc
     */
    protected function loadController($class)
    {
        if (!class_exists($class)) {
            // Try to guess the controller class if only a name is given
            $class = sprintf($this->controllerPattern, ucfirst($class));
        }
        if (!class_exists($class)) {
            throw new MissingControllerException("Controller {$class} is not found");
        }

        $controller = $this->container->get($class);
        if (!$controller instanceof Controller) {
            throw new InvalidControllerException("Class {$class} is not a valid controller");
        }

        return $controller;
    }

    protected function runController(
        $controller,
        $action,
        Request $request,
        Response $response
    ) {
        /** @var $controller Controller */
        return $controller->run($action, $request, $response);
    }

    /**
     * @inheritdoc
     */
    protected function getAction(Request $request, $controller)
    {
        /** @var $controller Controller */
        return $request->get()->get('action', $controller->getDefaultAction());
    }
}
