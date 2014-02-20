<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Controller;

use Closure;
use InvalidArgumentException;
use Miny\Controller\Exceptions\InvalidControllerException;
use Miny\Controller\Exceptions\MissingControllerException;
use Miny\Event\EventDispatcher;
use Miny\Factory\Container;
use Miny\HTTP\Request;
use Miny\HTTP\Response;

class ControllerCollection
{
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var (BaseController|Closure|string)[]
     */
    private $controllers = array();
    private $controllerNamespace;

    /**
     * @var Container
     */
    private $container;

    /**
     * @param string          $namespace
     * @param Container       $container
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct($namespace, Container $container, EventDispatcher $eventDispatcher)
    {
        $this->container           = $container;
        $this->eventDispatcher     = $eventDispatcher;
        $this->controllerNamespace = $namespace;
    }

    /**
     * @param BaseController|Closure|string $controller
     *
     * @param string                        $name
     *
     * @throws InvalidArgumentException
     * @throws InvalidControllerException
     * @return string
     *
     */
    public function register($controller, $name = null)
    {
        if ($name === null) {
            $name = is_string($controller) ? $controller : $this->getNextName();
        } elseif (!is_string($name)) {
            throw new InvalidArgumentException('Controller name must be a string');
        }
        if (!$this->isControllerValid($controller)) {
            $message = sprintf('Controller %s is invalid.', $name);
            throw new InvalidControllerException($message);
        }
        $this->controllers[$name] = $controller;

        return $name;
    }

    /**
     * @param $controller
     *
     * @return bool
     */
    protected function isControllerValid($controller)
    {
        if ($controller instanceof Closure) {
            return true;
        }

        return is_string($controller);
    }

    /**
     * @return string
     */
    public function getNextName()
    {
        return '_controller_' . count($this->controllers);
    }

    /**
     * Loads and runs the requested controller.
     * This method looks for the registered class.
     * If a string was registered it loads the controller from factory or instantiates it by its class name.
     * This method also raises two events (onControllerLoaded and onControllerFinished) that allow modifying
     * the behaviour of the controller.
     *
     * @param string   $class The controller name or alias in Factory.
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     * @throws InvalidArgumentException when the controller is not an instance of Controller or Closure
     */
    public function resolve($class, Request $request, Response $response)
    {
        $controller = $this->getController($class);
        $action     = $request->get('action');

        if (empty($action) && $controller instanceof Controller) {
            $action = $controller->getDefaultAction();
        }

        $event = $this->eventDispatcher->raiseEvent('onControllerLoaded', $controller, $action);

        if ($event->isHandled() && $event->getResponse() instanceof Response) {
            return $event->getResponse();
        }

        if ($controller instanceof Controller) {
            $retVal = $controller->run($action, $request, $response);
        } elseif ($controller instanceof Closure) {
            $retVal = $controller($request, $action, $response);
        } else {
            throw new InvalidArgumentException('Invalid controller: ' . $class);
        }

        $this->eventDispatcher->raiseEvent('onControllerFinished', $controller, $action, $retVal);

        return $response;
    }

    /**
     * @param string $class
     *
     * @throws InvalidArgumentException
     * @throws InvalidControllerException
     * @return BaseController|Closure
     */
    private function getController($class)
    {
        if (!is_string($class)) {
            throw new InvalidArgumentException('Controller name must be a string');
        }
        if (isset($this->controllers[$class])) {
            $class = $this->controllers[$class];
            if (!is_string($class)) {
                //In this case $class is a Closure
                return $class;
            }
        }
        $class = $this->getControllerClassName($class);

        $controller = $this->container->get($class);
        if (!$controller instanceof BaseController) {
            $message = sprintf('Class %s does not extend BaseController', $class);
            throw new InvalidControllerException($message);
        }

        return $controller;
    }

    /**
     * @param string $class
     *
     * @return string
     * @throws MissingControllerException
     */
    private function getControllerClassName($class)
    {
        if (class_exists($class)) {
            return $class;
        }
        $class = $this->controllerNamespace . ucfirst($class) . 'Controller';
        if (!class_exists($class)) {
            throw new MissingControllerException(sprintf('Class %s does not exist.', $class));
        }

        return $class;
    }
}
