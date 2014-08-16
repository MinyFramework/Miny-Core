<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Controller;

use Miny\Extendable;
use Miny\Factory\ParameterContainer;
use Miny\HTTP\Request;
use Miny\HTTP\Response;
use Miny\HTTP\ResponseHeaders;
use Miny\Router\RouteGenerator;
use Miny\Router\Router;

abstract class Controller extends Extendable
{
    /**
     * @var ParameterContainer
     */
    protected $parameterContainer;

    /**
     * @var Router
     */
    private $router;

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

    public function setRouter(Router $router)
    {
        $this->router = $router;
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
     * This method sets several extra helper methods for the controller. These methods are:
     *  * setCode - sets the HTTP status code.
     *  * redirect - sets a redirection response.
     *  * getHeaders - returns the Headers object of $response
     *  * cookie - sets a cookie.
     *  * header - Sets an HTTP header.
     *  * hasHeader - checks if a header is already set.
     *  * removeHeader - removes a header.
     *
     * @param string   $action
     * @param Request  $request
     * @param Response $response
     *
     * @return mixed The return value of the action.
     */
    public function run($action, Request $request, Response $response)
    {
        $this->response = $response;
        $this->headers  = $response->getHeaders();

        return $this->{$action . 'Action'}($request, $response);
    }
}
