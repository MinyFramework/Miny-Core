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
    protected $name;
    protected $default_action = 'index';
    private $view;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function __set($key, $value)
    {
        $this->view->$key = $value;
    }

    public function __get($key)
    {
        return $this->view->$key;
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

    public function run($action, Request $request, Response $response)
    {
        $router = $this->app->router;

        $this->addMethods($response,
                array(
            'setCode', 'redirect',
            'header' => 'setHeader',
            'cookie' => 'setCookie'
        ));
        $this->addMethod('route', array($router, 'generate'));
        $this->addMethod('service', array($this->app, '__get'));
        $this->addMethod('view', array($this->app->view, 'get'));
        $this->addMethod('redirectRoute',
                function($route, array $params = array()) use($response, $router) {
                    $path = $router->generate($route, $params);
                    $response->redirect($path);
                });

        $action = $action ? : $this->default_action;
        $fn = $action . 'Action';
        if (!method_exists($this, $fn)) {
            throw new InvalidArgumentException('Action not found: ' . $fn);
        }

        $this->view = $this->app->view->get($this->name . '/' . $action);

        $return = $this->$fn($request);
        if (!$response->isRedirect()) {
            if (is_string($return)) {
                $this->view->file = $return;
            }

            $this->view->app = $this->app;
            $this->view->controller = $this;
            echo $this->view->render();
        }
    }

}