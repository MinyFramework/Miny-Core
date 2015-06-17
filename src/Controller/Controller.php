<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Controller;

use Miny\Controller\Exceptions\IllegalStateException;
use Miny\Extendable;
use Miny\Factory\ParameterContainer;
use Miny\HTTP\Request;
use Miny\HTTP\Response;
use Miny\HTTP\ResponseHeaders;
use Miny\Log\Log;
use Miny\Router\RouteGenerator;

abstract class Controller extends Extendable
{
    /**
     * @var ParameterContainer
     */
    protected $parameterContainer;

    /**
     * @var RouteGenerator
     */
    private $routeGenerator;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var ResponseHeaders
     */
    private $headers;

    /**
     * @var Log
     */
    private $log;

    /**
     * @var string
     */
    private $className;

    /**
     * @var bool
     */
    private $initCalled = false;

    /**
     * Initialize the controller.
     */
    protected function init()
    {
        $this->initCalled = true;
        $this->className = get_class($this);
    }

    public function setLogger(Log $log)
    {
        $this->log = $log;
    }

    public function setRouteGenerator(RouteGenerator $routeGenerator)
    {
        $this->routeGenerator = $routeGenerator;
    }

    public function setParameterContainer(ParameterContainer $parameterContainer)
    {
        $this->parameterContainer = $parameterContainer;
    }

    /**
     * Writes a line to the log
     *
     * @param $level
     * @param $message
     * @param ... arguments to be replaced in $message. {@see http://php.net/sprintf}
     */
    public function log($level, $message)
    {
        if (func_num_args() > 3) {
            $args = array_slice(func_get_args(), 3);
            if (is_array($args[0])) {
                $args = $args[0];
            }
            $this->log->write($level, $this->className, $message, $args);
        } else {
            $this->log->write($level, $this->className, $message);
        }
    }

    /**
     * @return string The default action to be executed when the request does not specify any.
     */
    public function getDefaultAction()
    {
        return 'index';
    }

    /**
     * Shortcut to fetch a configuration value.
     *
     * @return mixed
     */
    public function getConfig()
    {
        return $this->parameterContainer->offsetGet(func_get_args());
    }

    public function setCode($code)
    {
        $this->response->setCode($code);
    }

    public function route($route, array $params = [])
    {
        return $this->routeGenerator->generate($route, $params);
    }

    public function redirect($url, $code = 301)
    {
        $this->response->redirect($url, $code);
    }

    public function redirectRoute($route, array $params = [])
    {
        $path = $this->routeGenerator->generate($route, $params);
        $this->response->redirect($path);
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function cookie($name, $value)
    {
        $this->response->setCookie($name, $value);
    }

    public function header($name, $value)
    {
        $this->headers->set($name, $value);
    }

    public function hasHeader($name, $value = null)
    {
        return $this->headers->has($name, $value);
    }

    public function removeHeader($name, $value = null)
    {
        $this->headers->remove($name, $value);
    }

    /**
     * Runs the controller.
     *
     * @param string   $action
     * @param Request  $request
     * @param Response $response
     *
     * @return mixed The return value of the action.
     */
    public function run($action, Request $request, Response $response)
    {
        $this->init();
        if (!$this->initCalled) {
            throw new IllegalStateException("Controller::init() must be called");
        }
        $this->response = $response;
        $this->headers  = $response->getHeaders();

        return $this->{$action . 'Action'}($request, $response);
    }
}
