<?php

/**
 * This file is part of the Miny framework.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version accepted by the author in accordance with section
 * 14 of the GNU General Public License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   Miny/Controller
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
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
    protected $templating;
    protected $name;
    protected $default_action = 'index';

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->templating = $app->templating;
    }

    public function __set($key, $value)
    {
        $this->templating->assign($key, $value);
    }

    public function __get($key)
    {
        return $this->templating->$key;
    }

    public function request($path, array $get = NULL, array $post = NULL, array $cookie = NULL)
    {
        $request = new Request($path, $get, $post, $cookie, Request::SUB_REQUEST);
        $response = $this->app->dispatcher->dispatch($request);

        foreach ($response->getHeaders() as $name => $value) {
            $this->header($name, $value);
        }

        foreach ($response->getCookies() as $name => $value) {
            $this->cookie($name, $value);
        }

        return $response;
    }

    public function run($action, Request $request)
    {
        $response = new Response;

        $router = $this->app->router;
        $this->addMethods($response, array(
            'setCode', 'redirect',
            'header' => 'setHeader',
            'cookie' => 'setCookie'
        ));
        $this->addMethod('route', array($router, 'generate'));
        $this->addMethod('assign', array($this->templating, 'assign'));
        $this->addMethod('service', array($this->app, '__get'));
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

        $this->templating->setScope('controller');
        $vars = $this->templating->getVariables();
        $return = $this->$fn($request);
        if (!$response->isRedirect() && $return !== false) {

            if (is_string($return)) {
                $template = $return;
            } else {
                $template = $this->name . '/' . $action;
            }

            $this->__set('controller', $this);
            $output = $this->templating->render($template);
            $response->setContent($output);
        }
        $this->templating->leaveScope(true);
        foreach ($vars as $k => $v) {
            $this->templating->assign($k, $v, 'controller');
        }
        return $response;
    }

}