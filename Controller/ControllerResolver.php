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

class ControllerResolver implements iControllerResolver {

    private $template;
    private $controllers = array();

    public function __construct(\Miny\Template\Template $template) {
        $this->template = $template;
    }

    public function register($name, $controller) {
        if (!is_string($controller) && !is_callable($controller) && !$controller instanceof \Closure) {
            throw new \InvalidArgumentException('Invalid controller: ' . $name . ' (' . gettype($controller) . ')');
        }
        if (!is_string($controller)) {
            $controller = func_get_args();
            array_shift($controller);
        }
        $this->controllers[$name] = $controller;
    }

    public function resolve($controller_class, $action = NULL, array $params = array()) {
        try {
            $controller = $this->getController($controller_class);
        } catch (\RuntimeException $e) {
            throw new \InvalidArgumentException('Controller not found: ' . $controller_class, 0, $e);
        }
        if (!$controller instanceof iController) {
            throw new \RuntimeException('Invalid controller: ' . $controller_class);
        }
        return $this->runController($controller, $controller_class, $action, $params);
    }

    private function getController($class) {
        if (!isset($this->controllers[$class])) {
            $controller = $this->getControllerFromClassName($class);
        } else {
            if (is_string($this->controllers[$class])) {
                $controller = $this->getControllerFromClassName($this->controllers[$class]);
            } else {
                $params = $this->controllers[$class];
                $callable = array_shift($params);
                $controller = call_user_func_array($callable, $params);
                if (!$controller instanceof iController) {
                    throw new \InvalidArgumentException('Invalid controller: ' . $class);
                }
            }
        }
        return $controller;
    }

    private function getControllerFromClassName($class) {
        $classpath = '\\Application\\Controller\\' . $class . 'Controller';
        if (!class_exists($classpath)) {
            throw new \InvalidArgumentException('Controller not found: ' . $class);
        }
        return new $classpath;
    }

    private function runController(iController $controller, $class, $action, array $params = array()) {
        $template_scope = $class . '_' . $action;
        $this->template->setScope($template_scope);
        $return = $controller->run($class, $action, $params);

        if (is_string($return)) {
            $response = new \Miny\HTTP\RedirectResponse($return);
        } else {
            $this->template->controller = $controller;
            foreach ($controller->getAssigns() as $key => $array) {
                list($value, $scope) = $array;
                $this->template->assign($key, $value, $scope ?: $template_scope);
            }
            $response = new \Miny\HTTP\Response($this->template->render($controller->getTemplate()), $controller->status());
        }
        foreach ($controller->getHeaders() as $name => $value) {
            $response->setHeader($name, $value);
        }
        foreach ($controller->getCookies() as $name => $value) {
            $response->setCookie($name, $value);
        }
        $this->template->leaveScope(true);
        return $response;
    }

}