<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Controller;

use Miny\Factory\ParameterContainer;
use Miny\HTTP\Request;
use Miny\HTTP\Response;
use Miny\HTTP\ResponseHeaders;
use Miny\Router\RouteGenerator;
use Miny\Router\Router;

abstract class Controller extends BaseController
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @var
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
     * @param Router             $router
     * @param RouteGenerator     $routeGenerator
     * @param ParameterContainer $parameterContainer
     */
    public function __construct(
        Router $router,
        RouteGenerator $routeGenerator,
        ParameterContainer $parameterContainer
    ) {
        $this->router         = $router;
        $this->routeGenerator = $routeGenerator;
        parent::__construct($parameterContainer);
    }

    /**
     * @return string The default action to be executed when the request does not specify any.
     */
    public function getDefaultAction()
    {
        return 'index';
    }

    protected function setCode($code)
    {
        $this->response->setCode($code);
    }

    protected function redirect($url, $code = 301)
    {
        $this->response->redirect($url, $code);
    }

    protected function getHeaders()
    {
        return $this->headers;
    }

    protected function cookie($name, $value)
    {
        $this->response->setCookie($name, $value);
    }

    protected function header($name, $value)
    {
        $this->headers->set($name, $value);
    }

    protected function hasHeader($name, $value = null)
    {
        return $this->headers->has($name, $value);
    }

    protected function removeHeader($name, $value = null)
    {
        $this->headers->remove($name, $value);
    }

    protected function redirectRoute($route, array $params = array())
    {
        $path = $this->routeGenerator->generate($route, $params);
        $this->response->redirect($path);
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
