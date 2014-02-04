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
use Miny\Event\EventDispatcher;
use Miny\Factory\Container;
use Miny\HTTP\Request;
use Miny\HTTP\Response;
use UnexpectedValueException;

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

    public function __construct(Container $container, EventDispatcher $eventDispatcher, $ns)
    {
        $this->container           = $container;
        $this->eventDispatcher     = $eventDispatcher;
        $this->controllerNamespace = $ns;
    }

    /**
     * @param string                        $name
     * @param BaseController|Closure|string $controller
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function register($controller, $name = null)
    {
        if ($name === null) {
            $name = is_string($controller) ? $controller : $this->getNextName();
        } elseif (!is_string($name)) {
            throw new InvalidArgumentException('Controller name must be a string');
        }
        if (!$controller instanceof Closure && !$controller instanceof BaseController && !is_string($controller)) {
            throw new InvalidArgumentException(sprintf('Controller %s is invalid.', $name));
        }
        $this->controllers[$name] = $controller;

        return $name;
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
     * @return \Miny\HTTP\Response
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

        if ($event->isHandled() && $event->hasResponse() && $event->getResponse() instanceof Response) {
            return $event->getResponse();
        }

        if ($controller instanceof Controller) {
            $retval = $controller->run($action, $request, $response);
        } elseif ($controller instanceof Closure) {
            $retval = $controller($request, $action, $response);
        } else {
            throw new InvalidArgumentException('Invalid controller: ' . $class);
        }

        $this->eventDispatcher->raiseEvent('onControllerFinished', $controller, $action, $retval);

        return $response;
    }

    /**
     * @param string $class
     *
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
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
                //In this case $class is either a Closure or a Controller
                return $class;
            }
        }
        $class = $this->checkControllerClassName($class);

        $controller = $this->container->get($class);
        if (!$controller instanceof BaseController) {
            throw new UnexpectedValueException('Class does not extend BaseController: ' . $class);
        }

        return $controller;
    }

    /**
     * @param $class
     *
     * @return string
     * @throws \UnexpectedValueException
     */
    private function checkControllerClassName($class)
    {
        if (class_exists($class)) {
            return $class;
        }
        $class = $this->controllerNamespace . ucfirst($class) . 'Controller';
        if (!class_exists($class)) {
            throw new UnexpectedValueException('Class not exists: ' . $class);
        }

        return $class;
    }
}
