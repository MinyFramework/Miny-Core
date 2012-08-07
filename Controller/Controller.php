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
    protected $view;
    protected $name;
    protected $default_action = 'index';
    private $view_descriptor;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->view = $app->view;
    }

    public function __set($key, $value)
    {
        $this->view_descriptor->$key = $value;
    }

    public function __get($key)
    {
        return $this->view_descriptor->$key;
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
        $this->addMethod('view', array($this->view, 'get'));
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

        $view = $this->view->get($this->name . '/' . $action);
        $this->view_descriptor = $view;
        $return = $this->$fn($request);
        if (!$response->isRedirect() && $return !== false) {
            if (is_string($return)) {
                $view->file = $return;
            }

            $view->app = $this->app;
            $view->controller = $this;
            echo $view->render();
        }
    }

}