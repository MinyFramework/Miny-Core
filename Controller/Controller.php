<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Controller;

use InvalidArgumentException;
use Miny\Application\Application;
use Miny\Extendable;
use Miny\HTTP\Request;
use Miny\HTTP\Response;

abstract class Controller extends Extendable
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var string
     */
    protected $default_action = 'index';

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->init();
    }

    protected function init()
    {

    }

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
     * @param string $type
     * @param string $template
     * @return View
     */
    protected function view($type, $template)
    {
        return $this->app->view_factory->$type($template);
    }

    /**
     * @param string $name
     * @return Object
     */
    protected function service($name)
    {
        return $this->app->$name;
    }

    /**
     * @param string $name
     * @param array $parameters
     * @return string
     */
    protected function route($name, array $parameters = array())
    {
        return $this->app->router->generate($name, $parameters);
    }

    /**
     * @param string $action
     * @param \Miny\HTTP\Request $request
     * @param \Miny\HTTP\Response $response
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
        $fn = $action . 'Action';
        if (!method_exists($this, $fn)) {
            throw new InvalidArgumentException('Action not found: ' . $action);
        }

        $this->$fn($request);
    }

}
