<?php

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
        $return = $controller->run($class, $action, $params);

        if (is_string($return)) {
            $response = new \Miny\HTTP\RedirectResponse($return);
        } else {
            $this->template->controller = $controller;
            foreach ($controller->getAssigns() as $key => $value) {
                $this->template->$key = $value;
            }
            $response = new \Miny\HTTP\Response($this->template->render($controller->getTemplate()), $controller->status());
        }
        foreach ($controller->getHeaders() as $name => $value) {
            $response->setHeader($name, $value);
        }
        foreach ($controller->getCookies() as $name => $value) {
            $response->setCookie($name, $value);
        }
        $this->template->clean();
        return $response;
    }

}