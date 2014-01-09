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
    public function request($path, array $get = null, array $post = null, array $cookie = null)
    {
        $response = $this->app->dispatch(new Request($path, $get, $post, $cookie, Request::SUB_REQUEST));
        $this->getHeaders()->addHeaders($response->getHeaders());

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
        $this->addMethods($response, array(
            'setCode', 'redirect', 'getHeaders',
            'cookie' => 'setCookie'
        ));
        $this->addMethods($response->getHeaders(), array(
            'header'       => 'set',
            'hasHeader'    => 'has',
            'removeHeader' => 'remove'
        ));

        $router = $this->app->router;
        $this->addMethod('redirectRoute', function ($route, array $params = array()) use ($response, $router) {
            $path = $router->generate($route, $params);
            $response->redirect($path);
        });

        $action = $action ? : $this->default_action;
        return $this->{$action . 'Action'}($request);
    }
}
