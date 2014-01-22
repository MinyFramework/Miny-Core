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
use Miny\Application\Application;
use Miny\HTTP\Request;
use Miny\HTTP\Response;
use UnexpectedValueException;

class ControllerCollection
{
    /**
     * @var (BaseController|Closure|string)[]
     */
    private $controllers = array();
    private $controller_namespace;

    /**
     * @var Application
     */
    private $application;

    public function __construct(Application $application, $ns)
    {
        $this->application          = $application;
        $this->controller_namespace = $ns;
    }

    /**
     * @param string $name
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
        }
        if (!is_string($name)) {
            throw new InvalidArgumentException('Controller name must be a string');
        }
        if (!$controller instanceof Closure && !$controller instanceof BaseController && !is_string($controller)) {
            throw new InvalidArgumentException(sprintf('Invalid controller: %s (%s)', $name, gettype($controller)));
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
     * @param string $class
     * @return BaseController|Closure
     * @throws UnexpectedValueException
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
        $factory = $this->application->getFactory();
        if ($factory->has($class . '_controller')) {
            return $factory->get($class . '_controller');
        }
        if (!class_exists($class)) {
            $class = $this->controller_namespace . ucfirst($class) . 'Controller';
            if (!class_exists($class)) {
                throw new UnexpectedValueException('Class not exists: ' . $class);
            }
        }
        if (!is_subclass_of($class, __NAMESPACE__ . '\BaseController')) {
            throw new UnexpectedValueException('Class does not extend BaseController: ' . $class);
        }
        return new $class($this->application);
    }

    /**
     * Loads and runs the requested controller.
     * This method looks for the registered class.
     * If a string was registered it loads the controller from factory or instantiates it by its classname.
     * This method also raises two events (onControllerLoaded and onControllerFinished) that allow modifying
     * the behaviour of the controller.
     *
     * @param string $class The controller name or alias in Factory.
     * @param Request $request
     * @param Response $response
     *
     * @throws InvalidArgumentException when the controller is not an instance of Controller or Closure
     */
    public function resolve($class, Request $request, Response $response)
    {
        $controller = $this->getController($class);
        $action     = $request->get('action');

        $event_handler = $this->application->getFactory()->events;

        if (empty($action)) {
            if ($controller instanceof Controller) {
                $action = $controller->getDefaultAction();
            }
        }

        $event = $event_handler->raiseEvent('onControllerLoaded', $controller, $action);

        if($event->isHandled() && $event->hasResponse() && $event->getResponse() instanceof Response) {
            return $event->getResponse();
        }

        if ($controller instanceof Controller) {
            $retval = $controller->run($action, $request, $response);
        } elseif ($controller instanceof Closure) {
            $retval = $controller($request, $action, $response);
        } else {
            throw new InvalidArgumentException('Invalid controller: ' . $class);
        }

        $event_handler->raiseEvent('onControllerFinished', $controller, $action, $retval);
        return $response;
    }
}
