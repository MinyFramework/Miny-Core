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

    public function getDefaultAction()
    {
        return 'index';
    }

    /**
     * @param string $action
     * @param Request $request
     * @param Response $response
     *
     * @return mixed
     *
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

        return $this->{$action . 'Action'}($request, $response);
    }
}
