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
     * @var BaseController|Closure|string []
     */
    private $controllers = array();

    /**
     * @var Application
     */
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * @param string $name
     * @param BaseController|Closure|string $controller
     * @throws InvalidArgumentException
     */
    public function register($name, $controller)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Controller name must be a string');
        }
        if (!$controller instanceof Closure && !$controller instanceof BaseController && !is_string($controller)) {
            throw new InvalidArgumentException(sprintf('Invalid controller: %s (%s)', $name, gettype($controller)));
        }
        $this->controllers[$name] = $controller;
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
    public function getController($class)
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
        if (!class_exists($class)) {
            $class = '\Application\Controllers\\' . ucfirst($class) . 'Controller';
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
     * @param string $class
     * @param string $action
     * @param Request $request
     * @param Response $response
     * @throws InvalidArgumentException
     */
    public function resolve($class, $action, Request $request, Response $response)
    {
        $controller = $this->getController($class);
        if ($controller instanceof Controller) {
            $controller->run($action, $request, $response);
        } elseif ($controller instanceof Closure) {
            $controller($request, $action, $response);
        } else {
            throw new InvalidArgumentException('Invalid controller: ' . $class);
        }
    }
}
