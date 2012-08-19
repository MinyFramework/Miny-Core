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
    private $view_assigns = array();

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function __set($key, $value)
    {
        $this->view_assigns[$key] = $value;
    }

    public function __get($key)
    {
        return $this->view_assigns[$key];
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
        $view_manager = $this->app->view;

        $this->addMethods($response,
                array(
            'setCode', 'redirect',
            'header' => 'setHeader',
            'cookie' => 'setCookie'
        ));
        $this->addMethod('route', array($router, 'generate'));
        $this->addMethod('service', array($this->app, '__get'));
        $this->addMethod('view', array($view_manager, 'get'));
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

        $file = $this->$fn($request);

        if (!$response->isRedirect()) {

            $view = $view_manager->get($file ? : $this->name . '/' . $action);

            $view->addVars($this->view_assigns);
            $view->app = $this->app;
            $view->controller = $this;
            echo $view->render();
        }
    }

}