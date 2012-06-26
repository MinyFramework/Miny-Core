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
 *
 */

namespace Miny\Controller;

use \Miny\HTTP\RedirectResponse;
use \Miny\HTTP\Response;
use \Miny\Template\Template;

class ControllerResolver
{
    private $templating;
    private $controllers = array();

    public function __construct(Template $templating)
    {
        $this->templating = $templating;
    }

    public function register($name, $controller)
    {
        if (!is_string($controller)
                && !is_callable($controller)
                && !$controller instanceof \Closure) {
            $type = gettype($controller);
            $message = sprintf('Invalid controller: %s (%s)', $name, $type);
            throw new \InvalidArgumentException($message);
        }
        if (!is_string($controller)) {
            $controller = func_get_args();
            array_shift($controller);
        }
        $this->controllers[$name] = $controller;
    }

    public function resolve($class, $action = NULL, array $params = array())
    {
        if (!isset($this->controllers[$class])) {
            $controller = $this->getController($class);
        } elseif (is_string($this->controllers[$class])) {
            $controller = $this->getController($this->controllers[$class]);
        } else {
            $factory_params = $this->controllers[$class];
            $callable = array_shift($factory_params);
            $controller = call_user_func_array($callable, $factory_params);
        }
        if (!$controller instanceof Controller) {
            $message = 'Controller must extend Controller: ' . $class;
            throw new \RuntimeException($message);
        }
        return $this->runController($controller, $class, $action, $params);
    }

    private function getController($class)
    {
        if (!class_exists($class)) {
            $message = 'Controller not found: ' . $class;
            throw new \InvalidArgumentException($message);
        }
        return new $class;
    }

    private function runController(Controller $controller, $class, $action,
            array $params = NULL)
    {
        $this->templating->setScope();

        $return = $controller->run($class, $action, $params);

        if (is_string($return)) {
            $response = new RedirectResponse($return);
        } else {
            $this->templating->controller = $controller;
            foreach ($controller->getAssigns() as $key => $array) {
                list($value, $scope) = $array;
                $this->templating->assign($key, $value, $scope);
            }
            $output = $this->templating->render($controller->template);
            $response = new Response($output, $controller->status);
        }

        foreach ($controller->getHeaders() as $name => $value) {
            $response->setHeader($name, $value);
        }
        foreach ($controller->getCookies() as $name => $value) {
            $response->setCookie($name, $value);
        }

        $this->templating->leaveScope(true);
        return $response;
    }

}