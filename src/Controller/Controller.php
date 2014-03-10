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
        $this->addMethods(
            $response,
            array(
                'setCode',
                'redirect',
                'getHeaders',
                'cookie' => 'setCookie'
            )
        );
        $this->addMethods(
            $response->getHeaders(),
            array(
                'header'       => 'set',
                'hasHeader'    => 'has',
                'removeHeader' => 'remove'
            )
        );

        $routeGenerator = $this->routeGenerator;
        $this->addMethod(
            'redirectRoute',
            function ($route, array $params = array()) use ($response, $routeGenerator) {
                $path = $routeGenerator->generate($route, $params);
                $response->redirect($path);
            }
        );

        return $this->{$action . 'Action'}($request, $response);
    }
}
