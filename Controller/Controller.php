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
    protected $app;
    protected $default_action = 'index';

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->init();
    }

    protected function init()
    {

    }

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

    protected function view($type, $template)
    {
        return $this->app->view_factory->get($type, $template);
    }

    protected function service($name)
    {
        return $this->app->$name;
    }

    protected function route($name, array $parameters = array())
    {
        return $this->app->router->generate($name, $parameters);
    }

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

        if ($response->isRedirect()) {
            return;
        }
    }

}