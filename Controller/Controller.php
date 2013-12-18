<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Controller;

use InvalidArgumentException;
use Miny\HTTP\Request;
use Miny\HTTP\Response;

abstract class Controller extends BaseController
{
    /**
     * @var string
     */
    protected $default_action = 'index';

    /**
     * @param string $path
     * @param array $get
     * @param array $post
     * @param array $cookie
     * @return Response
     */
    public function request($path, array $get = NULL, array $post = NULL, array $cookie = NULL)
    {
        $response = $this->app->dispatch(
                new Request($path, $get, $post, $cookie, Request::SUB_REQUEST));

        foreach ($response->getHeaders() as $name => $value) {
            $this->header($name, $value);
        }
        foreach ($response->getCookies() as $name => $value) {
            $this->cookie($name, $value);
        }
        return $response;
    }

    /**
     * @param string $name
     * @param array $parameters
     * @return string
     */
    public function route($name, array $parameters = array())
    {
        return $this->app->router->generate($name, $parameters);
    }

    /**
     * @param string $action
     * @param Request $request
     * @param Response $response
     * @throws InvalidArgumentException
     */
    public function run($action, Request $request, Response $response)
    {
        $router = $this->app->router;

        $this->addMethods($response,
                array(
            'setCode', 'redirect',
            'header' => 'setHeader',
            'cookie' => 'setCookie'
        ));
        $this->addMethod('redirectRoute',
                function($route, array $params = array()) use($response, $router) {
            $path = $router->generate($route, $params);
            $response->redirect($path);
        });

        $action = $action ? : $this->default_action;
        $fn     = $action . 'Action';
        if (!method_exists($this, $fn)) {
            throw new InvalidArgumentException('Action not found: ' . $action);
        }

        $this->$fn($request);
    }
}
