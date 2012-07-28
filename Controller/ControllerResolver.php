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

use \Miny\HTTP\RedirectResponse;
use \Miny\HTTP\Request;
use \Miny\HTTP\Response;
use \Miny\Template\Template;

class ControllerResolver
{
    private $templating;
    private $collection;

    public function __construct(Template $templating, ControllerCollection $collection = NULL)
    {
        $this->templating = $templating;
        $this->collection = $collection;
    }

    public function resolve($class, $action, Request $request)
    {
        $controller = $this->collection->getController($class);
        if (!$controller instanceof Controller) {
            $message = 'Controller must extend Controller: ' . $class;
            throw new \RuntimeException($message);
        }
        return $this->runController($controller, $class, $action, $request);
    }

    private function runController(Controller $controller, $class, $action, Request $request)
    {
        $this->templating->setScope('controller');
        $vars = $this->templating->getVariables();
        $return = $controller->run($class, $action, $request);

        $response = $this->getResponse($controller, $return);

        $this->templating->leaveScope(true);
        foreach ($vars as $k => $v) {
            $this->templating->assign($k, $v, 'controller');
        }
        return $response;
    }

    private function getResponse(Controller $controller, $return)
    {
        $response = new Response;
        if (is_string($return)) {
            $response->redirect($return);
        } else {
            $this->templating->controller = $controller;
            foreach ($controller->getAssigns() as $key => $array) {
                list($value, $scope) = $array;
                $this->templating->assign($key, $value, $scope);
            }
            $output = $this->templating->render($controller->template);
            $response->setCode($controller->status);
            $response->setContent($output);
        }

        foreach ($controller->getHeaders() as $name => $value) {
            $response->setHeader($name, $value);
        }
        foreach ($controller->getCookies() as $name => $value) {
            $response->setCookie($name, $value);
        }
        return $response;
    }

}