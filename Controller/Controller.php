<?php

namespace Miny\Controller;

abstract class Controller implements iController {

    private $default_action;
    private $status = 200;
    private $assigns = array();
    private $headers = array();//$http = array();
    private $cookies = array();
    private $services = array();
    private $template;

    public function __construct($default_action = NULL) {
        $this->default_action = $default_action;
    }

    public function __set($key, $value) {
        $this->assigns[$key] = $value;
    }
    
    public function service($key, $service) {
        $this->services[$key] = $service;
    }

    public function __get($key) {
        if (isset($this->services[$key])) {
            return $this->services[$key];
        } else {
            if (array_key_exists($key, $this->assigns)) {
                return $this->assigns[$key];
            }
            throw new \OutOfBoundsException('Variable not set: ' . $key);
        }
    }

    public function status($code = NULL) {
        if ($code) {
            $this->status = $code;
        } else {
            return $this->status;
        }
    }

    public function cookie($name, $value) {
        $this->cookies[$name] = $value;
    }

    public function header($name, $value) {
        $this->headers[$name] = $value;
    }
    
    public function getCookies() {
        return $this->cookies;
    }
    
    public function getHeaders() {
        return $this->headers;
    }
    
    public function getAssigns() {
        return $this->assigns;
    }
    
    public function setTemplate($template) {
        $this->template = $template;
    }
    
    public function getTemplate() {
        return $this->template;
    }

    public function request($path, array $get = array(), array $post = array()) {
        $request = new \Miny\HTTP\Request($path, $get, $post, \Miny\HTTP\Request::SUB_REQUEST);
        $response = $this->dispatcher->dispatch($request);//TODO: biztosítani kell, hogy ez egyáltalán létezzen - System::Event?

        foreach ($response->getHeaders() as $name => $value) {
            $this->header($name, $value);
        }

        foreach ($response->getCookies() as $name => $value) {
            $this->cookie($name, $value);
        }

        return $response;
    }

    public function run($controller, $action, array $params = NULL) {
        if (!$action) {
            $action = $this->default_action ? : 'index';
        }
        $fn = $action . 'Action';
        if (!method_exists($this, $fn)) {
            throw new \InvalidArgumentException('Action not found: ' . $fn);
        }
        $this->setTemplate($controller . '/' . $action);

        return $this->$fn($params);
    }
    

}